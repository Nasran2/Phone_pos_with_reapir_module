<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Services\CustomerTransactionService;
use App\Services\LorryAccessService;
use App\Support\Features;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    protected $transactionService;

    public function __construct(CustomerTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $query = Customer::withCount('sales');

        // Search by name, phone, email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active/inactive status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status === 'active');
        }

        $customers = $query->latest('id')->paginate(12)->withQueryString();

        // Calculate Summary Card Metrics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', true)->count();
        $totalDue = Customer::where('balance', '>', 0)->sum('balance');

        // Today's Payments from customer transactions (credits registered under payments)
        $todayPayments = CustomerTransaction::whereDate('transaction_date', now()->toDateString())
            ->where('type', 'payment')
            ->sum('credit');

        $summary = [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'total_due' => $totalDue,
            'today_payments' => $todayPayments,
        ];

        return view('customers.index', compact('customers', 'summary'));
    }

    public function show(Customer $customer)
    {
        if (! auth()->user()->hasPermission('customers.view') && ! app(LorryAccessService::class)->isLorryUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $customer->load(['sales.cashier']);

        // Fetch transaction history
        $transactions = CustomerTransaction::where('customer_id', $customer->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'tx_page')
            ->withQueryString();

        // Fetch sales history
        $sales = $customer->sales()->when(! Features::wholesaleEnabled(), fn ($q) => $q->where('pos_type', 'retail'))
            ->latest()
            ->paginate(10, ['*'], 'sales_page')
            ->withQueryString();

        $holdAmount = Cheque::where('customer_id', $customer->id)
            ->where('cheque_type', 'received')
            ->open()
            ->sum('hold_amount');

        $repairJobs = Features::repairEnabled() && auth()->user()->hasPermission('repair.view')
            ? $customer->repairJobs()->with(['device', 'repairStatus'])->latest()->paginate(10, ['*'], 'repair_page')->withQueryString() : null;

        return view('customers.show', compact('customer', 'transactions', 'sales', 'holdAmount', 'repairJobs'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);

        $customer = DB::transaction(function () use ($validated) {
            $openingBalance = floatval($validated['opening_balance'] ?? 0);
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'opening_balance' => $openingBalance,
                'credit_limit' => isset($validated['credit_limit']) ? floatval($validated['credit_limit']) : null,
                'status' => filter_var($validated['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $validated['notes'] ?? null,
                'balance' => 0.00, // adjusted next
                'created_by' => auth()->id(),
            ]);

            if ($openingBalance > 0) {
                $this->transactionService->recordTransaction(
                    $customer,
                    $openingBalance,
                    'opening_balance',
                    'OB-'.str_pad($customer->id, 5, '0', STR_PAD_LEFT),
                    'Initial opening balance'
                );
            }

            return $customer;
        });

        ActivityLog::log('Create', 'Customers Module', "Created customer {$customer->name}");

        return back()->with('success', 'Customer saved successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validateCustomer($request, $customer);

        DB::transaction(function () use ($customer, $validated) {
            $oldOpening = floatval($customer->opening_balance);
            $newOpening = floatval($validated['opening_balance'] ?? 0);

            $customer->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'opening_balance' => $newOpening,
                'credit_limit' => isset($validated['credit_limit']) ? floatval($validated['credit_limit']) : null,
                'status' => filter_var($validated['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            // Adjust balance if opening balance changed
            if ($oldOpening !== $newOpening) {
                $difference = $newOpening - $oldOpening;
                $this->transactionService->recordTransaction(
                    $customer,
                    $difference,
                    'opening_balance_adjustment',
                    'OBA-'.str_pad($customer->id, 5, '0', STR_PAD_LEFT),
                    'Opening balance adjusted from Rs. '.number_format($oldOpening, 2).' to Rs. '.number_format($newOpening, 2)
                );
            }
        });

        ActivityLog::log('Update', 'Customers Module', "Updated customer {$customer->name}");

        return back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'This customer has sales records and cannot be deleted.');
        }

        $name = $customer->name;

        DB::transaction(function () use ($customer) {
            // Keep the transaction history when soft-deleting a party.
            $customer->delete();
        });

        ActivityLog::log('Delete', 'Customers Module', "Deleted customer {$name}");

        return back()->with('success', 'Customer deleted successfully.');
    }

    public function storePayment(Request $request, Customer $customer)
    {
        if (! auth()->user()->hasPermission('customers.payment') && ! app(LorryAccessService::class)->isLorryUser(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $amount = floatval($validated['amount']);

        $this->transactionService->recordTransaction(
            $customer,
            -$amount, // Negative decreases due balance
            'payment',
            $validated['reference_no'] ?? 'PAY-'.strtoupper(uniqid()),
            $validated['note'] ?? ('Recorded payment via '.$validated['payment_method']),
            null
        );

        return back()->with('success', 'Payment of Rs. '.number_format($amount, 2).' recorded successfully.');
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:1,0,true,false'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
