<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\ProductStoreStock;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\StockHistory;
use App\Services\JournalService;
use App\Services\LorryAccessService;
use App\Services\SaleInventoryService;
use App\Support\Features;
use App\Support\SystemSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use DOMElement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('customer')->withCount('items');
        $query->when(! Features::wholesaleEnabled(), fn ($q) => $q->where('pos_type', 'retail'));

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

        if ($request->boolean('pending_only')) {
            $query->whereNull('stock_deducted_at')->where('status', '!=', 'cancelled');
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
        $summary = $this->summary();

        return view('sales.index', compact('sales', 'summary'));
    }

    public function show(Sale $sale)
    {
        if (! auth()->user()->hasPermission('sales.invoice_view') && ! app(LorryAccessService::class)->isLorryUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'payments.creator', 'invoicePayments.cheque', 'returns.items.product']);

        return view('sales.show', compact('sale'));
    }

    public function print(Sale $sale)
    {
        if (! auth()->user()->hasPermission('sales.print') && ! app(LorryAccessService::class)->isLorryUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'items.productSerial', 'payments', 'invoicePayments.cheque', 'taxTransactions']);

        return view('sales.print', compact('sale'));
    }

    public function shared(Request $request, Sale $sale)
    {
        abort_unless($request->hasValidSignature(), 403, 'This invoice link is invalid or has expired.');

        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'items.productSerial', 'payments', 'invoicePayments.cheque', 'taxTransactions']);

        return view('sales.print', ['sale' => $sale, 'shared' => true]);
    }

    public function download(Sale $sale)
    {
        if (! auth()->user()->hasPermission('sales.print') && ! app(LorryAccessService::class)->isLorryUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['customer', 'cashier', 'items.product', 'items.variant', 'items.productSerial', 'payments', 'invoicePayments.cheque', 'taxTransactions']);

        $isPdf = true;

        $pdf = Pdf::loadView('sales.print', compact('sale', 'isPdf'))
            ->setOption('defaultMediaType', 'print')
            ->setPaper('a4', 'portrait');

        // Match the print layout's bottom-aligned footer after pagination is resolved.
        $pdf->getDomPDF()->setCallbacks([[
            'event' => 'begin_frame',
            'f' => static function ($frame, $canvas) {
                $node = $frame->get_node();
                if (! ($node instanceof DOMElement) || $node->getAttribute('class') !== 'invoice-footer') {
                    return;
                }

                $box = $frame->get_border_box();
                // The print page has a 10mm margin and 2mm bottom sheet padding.
                $offset = $canvas->get_height() - (12 * 72 / 25.4) - $box['y'] - $box['h'];
                if ($offset > 0) {
                    $frame->move(0, $offset);
                }
            },
        ]]);

        return $pdf->download($sale->invoice_no.'.pdf');
    }

    public function storePayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['Cash', 'Bank Transfer', 'Card', 'QR Payment'])],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'deduct_stock_now' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($sale, $validated) {
            $sale = Sale::query()->with('customer')->lockForUpdate()->findOrFail($sale->id);
            $amount = (float) $validated['amount'];
            $oldDue = (float) $sale->due_amount;

            if ($sale->status === 'cancelled') {
                throw ValidationException::withMessages(['amount' => 'Payments cannot be added to a cancelled invoice.']);
            }

            if ($oldDue <= 0.009) {
                throw ValidationException::withMessages(['amount' => 'This invoice is already fully paid.']);
            }

            if ($amount > $oldDue + 0.009) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the current due amount.']);
            }

            if ($amount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $amount,
                    'payment_method' => $validated['payment_method'],
                    'reference_no' => $validated['reference_no'] ?? null,
                    'note' => $validated['note'] ?? 'Due payment',
                    'created_by' => auth()->id(),
                ]);
            }

            $sale->paid_amount = (float) $sale->paid_amount + $amount;
            $sale->due_amount = max(0, $oldDue - $amount);
            $sale->payment_status = $this->paymentStatus($sale);
            $sale->save();

            $deductStockNow = (bool) ($validated['deduct_stock_now'] ?? false);
            if (array_key_exists('deduct_stock_now', $validated)) {
                $sale->forceFill(['stock_release_on_payment' => $deductStockNow])->save();
            }
            $inventoryFinalized = false;

            try {
                $inventory = app(SaleInventoryService::class);
                if ($deductStockNow) {
                    $inventoryFinalized = $inventory->finalizeNow(
                        $sale,
                        'Stock deducted manually while '.SystemSettings::money((float) $sale->due_amount).' remained due.',
                    );
                } else {
                    $inventoryFinalized = $inventory->finalizeIfPaid($sale);
                }
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['amount' => $e->getMessage()]);
            }

            if ($amount > 0 && $sale->customer && $oldDue > 0) {
                $this->adjustCustomerBalance($sale->customer, -min($amount, $oldDue), 'sale_due_payment', $sale, $sale->invoice_no);
            }

            $activity = "Recorded payment for invoice {$sale->invoice_no}";
            if ($deductStockNow && $inventoryFinalized) {
                $activity .= ' and manually finalized inventory before full payment';
            }
            ActivityLog::log('Payment', 'Sales Module', $activity);
        });

        return back()->with('success', 'Payment recorded successfully. Inventory was updated when required.');
    }

    public function updateStockRelease(Request $request, Sale $sale, LorryAccessService $access)
    {
        $validated = $request->validate(['stock_release_enabled' => ['required', 'boolean']]);
        if ($access->isLorryUser($request->user()) && ! $access->isUnrestricted($request->user())) {
            abort_unless($sale->source_type === 'lorry' && $sale->lorry_id, 403);
            $access->authorize($request->user(), (int) $sale->lorry_id);
        }

        DB::transaction(function () use ($sale, $validated) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if (! in_array($sale->status, ['final', 'completed'], true)) {
                throw ValidationException::withMessages(['stock_release_enabled' => 'Stock release can only be edited on an active invoice.']);
            }
            if ($sale->returns()->exists()) {
                throw ValidationException::withMessages(['stock_release_enabled' => 'This invoice has product returns. Stock release cannot be changed.']);
            }

            $release = (bool) $validated['stock_release_enabled'];
            $sale->forceFill(['stock_release_on_payment' => $release])->save();
            try {
                $inventory = app(SaleInventoryService::class);
                $changed = $release
                    ? $inventory->finalizeNow($sale, 'Stock release enabled by operator.')
                    : $inventory->restore($sale, 'Stock returned to customer allocation by operator.', 'sale_reallocated');
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['stock_release_enabled' => $e->getMessage()]);
            }
            if ($changed) {
                ActivityLog::log('Stock Release', 'Sales Module', ($release ? 'Released stock for invoice ' : 'Returned stock to allocation for invoice ').$sale->invoice_no);
            }
        });

        return back()->with('success', $validated['stock_release_enabled'] ? 'Stock release saved. Payments were unchanged.' : 'Products returned to allocated stock. Payments were unchanged.');
    }

    public function destroy(Sale $sale)
    {
        $sale->load(['items.product', 'items.variant', 'customer', 'returns']);

        if ($sale->cheques()->exists()) {
            return back()->with('error', 'This sale has cheque records. Reverse the cheque through Cheque Management before cancelling the invoice.');
        }

        if ($sale->returns()->exists()) {
            return back()->with('error', 'Delete related sale returns before deleting this sale.');
        }

        DB::transaction(function () use ($sale) {
            app(SaleInventoryService::class)->restore($sale, 'Sale deleted');

            if ($sale->customer && (float) $sale->due_amount > 0) {
                $this->adjustCustomerBalance($sale->customer, -(float) $sale->due_amount, 'sale_deleted', $sale, $sale->invoice_no);
            }

            $invoiceNo = $sale->invoice_no;
            $journals = app(JournalService::class);
            $journals->deleteEntryByReference('Sale', $sale->id);
            foreach ($sale->invoicePayments as $payment) {
                $journals->deleteEntryByReference('SalePayment', $payment->id);
            }
            $sale->taxTransactions()->delete();
            $sale->payments()->delete();
            $sale->invoicePayments()->delete();
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
        $base->when(! Features::wholesaleEnabled(), fn ($q) => $q->where('pos_type', 'retail'));

        return [
            'today_sales' => (clone $base)->whereDate('created_at', $today)->sum('payable_amount'),
            'retail_sales' => (clone $base)->where('pos_type', 'retail')->sum('payable_amount'),
            ...(Features::wholesaleEnabled() ? ['wholesale_sales' => (clone $base)->where('pos_type', 'wholesale')->sum('payable_amount')] : []),
            'due_sales' => (clone $base)->sum('due_amount'),
            'total_profit' => (clone $base)->sum('total_profit_amount'),
            'pending_orders' => (clone $base)->whereNull('stock_deducted_at')->count(),
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
                'store_id' => $item->sale->store_id ?? Setting::get('default_store_id', 1),
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
            'store_id' => $item->sale->store_id ?? Setting::get('default_store_id', 1),
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
        $text = "BT\n/F1 18 Tf\n50 780 Td\n(".$this->pdfText($title).") Tj\n/F1 11 Tf\n0 -28 Td\n";
        foreach ($lines as $line) {
            $text .= '('.$this->pdfText($line).") Tj\n0 -18 Td\n";
        }
        $text .= 'ET';

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            '5 0 obj << /Length '.strlen($text)." >> stream\n{$text}\nendstream endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
