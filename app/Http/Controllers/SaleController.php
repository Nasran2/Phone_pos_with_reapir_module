<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockHistory;
use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'cashier'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('sale_type')) {
            $query->where('pos_type', $request->input('sale_type'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('cashier_id')) {
            $query->where('created_by', $request->input('cashier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sales = $query->latest('created_at')->paginate(12)->withQueryString();
        $cashiers = User::whereIn('id', Sale::query()->whereNotNull('created_by')->select('created_by'))->orderBy('name')->get();
        $summary = $this->summary();

        return view('sales.index', compact('sales', 'cashiers', 'summary'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'payments.creator', 'returns.items.product']);

        return view('sales.show', compact('sale'));
    }

    public function print(Sale $sale)
    {
        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'payments']);

        return view('sales.print', compact('sale'));
    }

    public function download(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'items.variant']);
        $lines = [
            'Invoice: ' . $sale->invoice_no,
            'Date: ' . $sale->created_at->format('Y-m-d h:i A'),
            'Customer: ' . ($sale->customer->name ?? 'Walk-in Customer'),
            'Sale Type: ' . ucfirst($sale->pos_type),
            'Grand Total: ' . SystemSettings::money((float) $sale->payable_amount),
            'Paid: ' . SystemSettings::money((float) $sale->paid_amount),
            'Due: ' . SystemSettings::money((float) $sale->due_amount),
        ];

        foreach ($sale->items as $item) {
            $name = $item->product->name ?? 'Product';
            $variant = $item->variantLabel();
            $lines[] = trim($name . ($variant ? ' - ' . $variant : '')) . ' x ' . (float) $item->quantity . ' = ' . SystemSettings::money((float) ($item->line_total ?: $item->subtotal));
        }

        return response($this->simplePdf('Sales Invoice ' . $sale->invoice_no, $lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $sale->invoice_no . '.pdf"',
        ]);
    }

    public function storePayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . max((float) $sale->due_amount, 0.01)],
            'payment_method' => ['required', Rule::in(['Cash', 'Bank Transfer', 'Card', 'QR Payment', 'Cheque', 'Credit / Due'])],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($sale, $validated) {
            $amount = (float) $validated['amount'];
            $oldDue = (float) $sale->due_amount;

            SalePayment::create([
                'sale_id' => $sale->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'note' => $validated['note'] ?? 'Due payment',
                'created_by' => auth()->id(),
            ]);

            $sale->paid_amount = (float) $sale->paid_amount + $amount;
            $sale->due_amount = max(0, $oldDue - $amount);
            $sale->payment_status = $this->paymentStatus($sale);
            $sale->save();

            if ($sale->customer && $oldDue > 0) {
                $this->adjustCustomerBalance($sale->customer, -min($amount, $oldDue), 'sale_due_payment', $sale, $sale->invoice_no);
            }

            ActivityLog::log('Payment', 'Sales Module', "Recorded payment for invoice {$sale->invoice_no}");
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Sale $sale)
    {
        $sale->load(['items.product', 'items.variant', 'customer', 'returns']);

        if ($sale->returns()->exists()) {
            return back()->with('error', 'Delete related sale returns before deleting this sale.');
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                $this->restoreStock($item, 'sale_deleted', 'Sale deleted');
            }

            if ($sale->customer && (float) $sale->due_amount > 0) {
                $this->adjustCustomerBalance($sale->customer, -(float) $sale->due_amount, 'sale_deleted', $sale, $sale->invoice_no);
            }

            DB::table('product_serials')->where('sale_id', $sale->id)->update([
                'status' => 'available',
                'sale_id' => null,
                'updated_at' => now(),
            ]);

            $invoiceNo = $sale->invoice_no;
            $sale->payments()->delete();
            $sale->items()->delete();
            $sale->delete();

            ActivityLog::log('Delete', 'Sales Module', "Deleted sale {$invoiceNo}");
        });

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    private function summary(): array
    {
        $today = now()->toDateString();
        $base = Sale::query()->where('status', '!=', 'cancelled');

        return [
            'today_sales' => (clone $base)->whereDate('created_at', $today)->sum('payable_amount'),
            'retail_sales' => (clone $base)->where('pos_type', 'retail')->sum('payable_amount'),
            'wholesale_sales' => (clone $base)->where('pos_type', 'wholesale')->sum('payable_amount'),
            'due_sales' => (clone $base)->sum('due_amount'),
            'total_profit' => (clone $base)->sum('total_profit_amount'),
        ];
    }

    private function paymentStatus(Sale $sale): string
    {
        if ($sale->status === 'cancelled') {
            return 'Cancelled';
        }

        if ((float) $sale->due_amount <= 0.009) {
            return 'Paid';
        }

        return (float) $sale->paid_amount > 0 ? 'Partial' : 'Due';
    }

    private function restoreStock(object $item, string $type, string $note): void
    {
        if ($item->variant_id) {
            DB::table('product_variants')->where('id', $item->variant_id)->increment('stock_quantity', (float) $item->quantity);
            $newQuantity = (float) DB::table('product_variants')->where('id', $item->variant_id)->value('stock_quantity');
        } else {
            $stock = ProductStoreStock::firstOrCreate([
                'product_id' => $item->product_id,
                'store_id' => $item->sale->store_id ?? \App\Models\Setting::get('default_store_id', 1)
            ], ['quantity' => 0]);
            $stock->quantity = (float) $stock->quantity + (float) $item->quantity;
            $stock->save();
            $newQuantity = (float) $stock->quantity;
        }

        StockHistory::create([
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'quantity_change' => (float) $item->quantity,
            'new_quantity' => $newQuantity,
            'type' => $type,
            'reference_no' => $item->sale->invoice_no ?? null,
            'note' => $note,
            'created_by' => auth()->id(),
            'store_id' => $item->sale->store_id ?? \App\Models\Setting::get('default_store_id', 1),
        ]);

        if (Schema::hasTable('stock_movements')) {
            DB::table('stock_movements')->insert([
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'type' => $type,
                'reference_id' => $item->sale_id,
                'reference_no' => $item->sale->invoice_no ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function adjustCustomerBalance(Customer $customer, float $amount, string $type, ?object $source = null, ?string $referenceNo = null): void
    {
        if (abs($amount) < 0.01) {
            return;
        }

        $customer->balance = (float) $customer->balance + $amount;
        $customer->save();

        CustomerTransaction::create([
            'customer_id' => $customer->id,
            'transactionable_type' => $source ? $source::class : null,
            'transactionable_id' => $source->id ?? null,
            'transaction_date' => now()->toDateString(),
            'type' => $type,
            'debit' => $amount > 0 ? $amount : 0,
            'credit' => $amount < 0 ? abs($amount) : 0,
            'balance_after' => $customer->balance,
            'reference_no' => $referenceNo,
            'note' => str_replace('_', ' ', $type),
            'created_by' => auth()->id(),
        ]);
    }

    private function simplePdf(string $title, array $lines): string
    {
        $text = "BT\n/F1 18 Tf\n50 780 Td\n(" . $this->pdfText($title) . ") Tj\n/F1 11 Tf\n0 -28 Td\n";
        foreach ($lines as $line) {
            $text .= '(' . $this->pdfText($line) . ") Tj\n0 -18 Td\n";
        }
        $text .= "ET";

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Length " . strlen($text) . " >> stream\n{$text}\nendstream endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
