<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\SupplierTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    protected $transactionService;

    public function __construct(SupplierTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $query = Supplier::withCount('purchases');

        // Search by name, company name, phone
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by active/inactive status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status === 'active');
        }

        $suppliers = $query->latest('id')->paginate(12)->withQueryString();

        // Calculate Summary Card Metrics
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', true)->count();
        $totalPayable = Supplier::where('balance', '>', 0)->sum('balance');
        
        // Today's Payments from supplier transactions (credits registered under payments)
        $todayPayments = SupplierTransaction::whereDate('transaction_date', now()->toDateString())
            ->where('type', 'payment')
            ->sum('credit');

        $summary = [
            'total_suppliers' => $totalSuppliers,
            'active_suppliers' => $activeSuppliers,
            'total_payable' => $totalPayable,
            'today_payments' => $todayPayments,
        ];

        return view('suppliers.index', compact('suppliers', 'summary'));
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases']);

        // Fetch transaction history
        $transactions = SupplierTransaction::where('supplier_id', $supplier->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'tx_page')
            ->withQueryString();

        // Fetch purchases history
        $purchases = $supplier->purchases()
            ->latest()
            ->paginate(10, ['*'], 'purchases_page')
            ->withQueryString();

        return view('suppliers.show', compact('supplier', 'transactions', 'purchases'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);

        $supplier = DB::transaction(function () use ($validated) {
            $openingBalance = floatval($validated['opening_balance'] ?? 0);
            $supplier = Supplier::create([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'opening_balance' => $openingBalance,
                'bank_details' => $validated['bank_details'] ?? null,
                'status' => filter_var($validated['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $validated['notes'] ?? null,
                'balance' => 0.00, // adjusted next
                'created_by' => auth()->id(),
            ]);

            if ($openingBalance > 0) {
                $this->transactionService->recordTransaction(
                    $supplier,
                    $openingBalance,
                    'opening_balance',
                    'OB-' . str_pad($supplier->id, 5, '0', STR_PAD_LEFT),
                    'Initial opening balance'
                );
            }

            return $supplier;
        });

        ActivityLog::log('Create', 'Suppliers Module', "Created supplier {$supplier->name}");

        return back()->with('success', 'Supplier saved successfully.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $this->validateSupplier($request, $supplier);

        DB::transaction(function () use ($supplier, $validated) {
            $oldOpening = floatval($supplier->opening_balance);
            $newOpening = floatval($validated['opening_balance'] ?? 0);

            $supplier->update([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'opening_balance' => $newOpening,
                'bank_details' => $validated['bank_details'] ?? null,
                'status' => filter_var($validated['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id()
            ]);

            // Adjust balance if opening balance changed
            if ($oldOpening !== $newOpening) {
                $difference = $newOpening - $oldOpening;
                $this->transactionService->recordTransaction(
                    $supplier,
                    $difference,
                    'opening_balance_adjustment',
                    'OBA-' . str_pad($supplier->id, 5, '0', STR_PAD_LEFT),
                    "Opening balance adjusted from Rs. " . number_format($oldOpening, 2) . " to Rs. " . number_format($newOpening, 2)
                );
            }
        });

        ActivityLog::log('Update', 'Suppliers Module', "Updated supplier {$supplier->name}");

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'This supplier has purchase records and cannot be deleted.');
        }

        $name = $supplier->name;
        
        DB::transaction(function () use ($supplier) {
            // Keep the transaction history when soft-deleting a party.
            $supplier->delete();
        });

        ActivityLog::log('Delete', 'Suppliers Module', "Deleted supplier {$name}");

        return back()->with('success', 'Supplier deleted successfully.');
    }

    public function storePayment(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $amount = floatval($validated['amount']);

        $this->transactionService->recordTransaction(
            $supplier,
            -$amount, // Negative decreases payable balance
            'payment',
            $validated['reference_no'] ?? 'PAY-' . strtoupper(uniqid()),
            $validated['note'] ?? ('Recorded payment via ' . $validated['payment_method']),
            null
        );

        return back()->with('success', 'Payment of Rs. ' . number_format($amount, 2) . ' recorded successfully.');
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'bank_details' => ['nullable', 'string'],
            'status' => ['nullable', 'in:1,0,true,false'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
