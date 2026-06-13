<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('sales');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest('id')->paginate(12)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales.payments', 'sales.returns']);

        $payments = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->select(
                'sale_payments.payment_date as date',
                'sales.invoice_no as reference_no',
                'sale_payments.payment_method as method',
                'sale_payments.amount',
                'sale_payments.note'
            )
            ->orderByDesc('sale_payments.payment_date')
            ->orderByDesc('sale_payments.id')
            ->get();

        $generalPayments = DB::table('customer_payments')
            ->where('customer_id', $customer->id)
            ->select('payment_date as date', 'reference_no', 'payment_method as method', 'amount', 'note')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $paymentHistory = $payments->merge($generalPayments)->sortByDesc('date')->values();
        $ledger = $this->customerLedger($customer, $paymentHistory);

        return view('customers.show', compact('customer', 'paymentHistory', 'ledger'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);

        $customer = Customer::create($validated + [
            'balance' => $validated['balance'] ?? 0,
            'points' => $validated['points'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('Create', 'Customers Module', "Created customer {$customer->name}");

        return back()->with('success', 'Customer saved successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validateCustomer($request, $customer);
        $customer->update($validated + ['updated_by' => auth()->id()]);

        ActivityLog::log('Update', 'Customers Module', "Updated customer {$customer->name}");

        return back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'This customer has sales and cannot be deleted.');
        }

        $name = $customer->name;
        $customer->delete();

        ActivityLog::log('Delete', 'Customers Module', "Deleted customer {$name}");

        return back()->with('success', 'Customer deleted successfully.');
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'points' => ['nullable', 'integer', 'min:0'],
            'balance' => ['nullable', 'numeric'],
        ]);
    }

    private function customerLedger(Customer $customer, $paymentHistory)
    {
        $rows = collect();

        foreach ($customer->sales as $sale) {
            $rows->push((object) [
                'date' => $sale->created_at,
                'type' => 'Sale',
                'reference_no' => $sale->invoice_no,
                'description' => ucfirst($sale->pos_type) . ' invoice',
                'debit' => (float) $sale->payable_amount,
                'credit' => 0,
            ]);

            foreach ($sale->returns as $return) {
                $rows->push((object) [
                    'date' => $return->created_at,
                    'type' => 'Return',
                    'reference_no' => $return->return_no,
                    'description' => 'Sale return',
                    'debit' => 0,
                    'credit' => (float) $return->total_amount,
                ]);
            }
        }

        foreach ($paymentHistory as $payment) {
            $rows->push((object) [
                'date' => $payment->date,
                'type' => 'Payment',
                'reference_no' => $payment->reference_no,
                'description' => $payment->method,
                'debit' => 0,
                'credit' => (float) $payment->amount,
            ]);
        }

        $balance = 0;

        return $rows->sortBy('date')->values()->map(function ($row) use (&$balance) {
            $balance += (float) $row->debit - (float) $row->credit;
            $row->balance = $balance;

            return $row;
        })->reverse()->values();
    }
}
