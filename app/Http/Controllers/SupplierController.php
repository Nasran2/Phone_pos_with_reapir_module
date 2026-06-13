<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchases');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest('id')->paginate(12)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases.payments', 'purchases.returns']);

        $payments = DB::table('purchase_payments')
            ->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->where('purchases.supplier_id', $supplier->id)
            ->select(
                'purchase_payments.payment_date as date',
                'purchases.invoice_no as reference_no',
                'purchase_payments.payment_method as method',
                'purchase_payments.amount',
                'purchase_payments.note'
            )
            ->orderByDesc('purchase_payments.payment_date')
            ->orderByDesc('purchase_payments.id')
            ->get();

        $generalPayments = DB::table('supplier_payments')
            ->where('supplier_id', $supplier->id)
            ->select('payment_date as date', 'reference_no', 'payment_method as method', 'amount', 'note')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $paymentHistory = $payments->merge($generalPayments)->sortByDesc('date')->values();
        $ledger = $this->supplierLedger($supplier, $paymentHistory);

        return view('suppliers.show', compact('supplier', 'paymentHistory', 'ledger'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);

        $supplier = Supplier::create($validated + [
            'balance' => $validated['balance'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('Create', 'Suppliers Module', "Created supplier {$supplier->name}");

        return back()->with('success', 'Supplier saved successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $this->validateSupplier($request);
        $supplier->update($validated + ['updated_by' => auth()->id()]);

        ActivityLog::log('Update', 'Suppliers Module', "Updated supplier {$supplier->name}");

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'This supplier has purchases and cannot be deleted.');
        }

        $name = $supplier->name;
        $supplier->delete();

        ActivityLog::log('Delete', 'Suppliers Module', "Deleted supplier {$name}");

        return back()->with('success', 'Supplier deleted successfully.');
    }

    private function validateSupplier(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'balance' => ['nullable', 'numeric'],
        ]);
    }

    private function supplierLedger(Supplier $supplier, $paymentHistory)
    {
        $rows = collect();

        foreach ($supplier->purchases as $purchase) {
            $rows->push((object) [
                'date' => $purchase->created_at,
                'type' => 'Purchase',
                'reference_no' => method_exists($purchase, 'displayNo') ? $purchase->displayNo() : $purchase->invoice_no,
                'description' => 'Purchase invoice',
                'debit' => 0,
                'credit' => (float) ($purchase->grand_total ?: $purchase->total_amount),
            ]);

            foreach ($purchase->returns as $return) {
                $rows->push((object) [
                    'date' => $return->created_at,
                    'type' => 'Return',
                    'reference_no' => $return->return_no,
                    'description' => 'Purchase return',
                    'debit' => (float) $return->total_amount,
                    'credit' => 0,
                ]);
            }
        }

        foreach ($paymentHistory as $payment) {
            $rows->push((object) [
                'date' => $payment->date,
                'type' => 'Payment',
                'reference_no' => $payment->reference_no,
                'description' => $payment->method,
                'debit' => (float) $payment->amount,
                'credit' => 0,
            ]);
        }

        $balance = 0;

        return $rows->sortBy('date')->values()->map(function ($row) use (&$balance) {
            $balance += (float) $row->credit - (float) $row->debit;
            $row->balance = $balance;

            return $row;
        })->reverse()->values();
    }
}
