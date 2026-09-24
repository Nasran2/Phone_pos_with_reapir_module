<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = Expense::with(['category', 'paidFrom', 'creator'])->latest();

        // Filters
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('expense_date', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('expense_date', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('expense_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('expense_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('expense_date', Carbon::now()->month)->whereYear('expense_date', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('expense_date', Carbon::now()->subMonth()->month)->whereYear('expense_date', Carbon::now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        } else {
            // Default to today
            $query->whereDate('expense_date', Carbon::today());
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(20)->withQueryString();
        $categories = ExpenseCategory::all();
        $paymentAccounts = Account::whereIn('account_type', ['Asset', 'Liability'])->where('status', true)->get();
        $expenseAccounts = Account::where('account_type', 'Expense')->where('status', true)->get();

        // Summary cards logic based on current query or general
        $todayTotal = Expense::whereDate('expense_date', Carbon::today())->sum('amount');
        $monthTotal = Expense::whereMonth('expense_date', Carbon::now()->month)->whereYear('expense_date', Carbon::now()->year)->sum('amount');
        $cashTotal = Expense::where('payment_method', 'Cash')->sum('amount');
        $bankTotal = Expense::whereIn('payment_method', ['Bank Transfer', 'Cheque', 'Online Payment'])->sum('amount');

        return view('accounting.expenses.index', compact('expenses', 'categories', 'paymentAccounts', 'expenseAccounts', 'todayTotal', 'monthTotal', 'cashTotal', 'bankTotal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_account_id' => 'required|exists:accounts,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'reference_no' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['expense_no'] = 'EXP-'.strtoupper(uniqid());

        // Handle attachment if any
        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('expenses', 'public');
        }

        DB::transaction(function () use ($validated, $request) {
            $expense = Expense::create($validated);

            // Double Entry Journal
            $this->journalService->createEntry(
                $expense->expense_date,
                'Expense',
                $expense->id,
                $expense->expense_no,
                'Expense: '.$expense->title,
                [
                    [
                        'account_id' => $request->expense_account_id,
                        'debit' => $expense->amount,
                        'credit' => 0,
                        'notes' => 'Expense incurred',
                    ],
                    [
                        'account_id' => $expense->paid_from_account_id,
                        'debit' => 0,
                        'credit' => $expense->amount,
                        'notes' => 'Payment from '.$expense->payment_method,
                    ],
                ],
                true,
                $expense->created_by
            );

            // Activity Log
            \DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => 'Created Expense',
                'module' => 'Expenses',
                'description' => "Created expense {$expense->expense_no} for amount {$expense->amount}",
                'created_at' => now(),
            ]);
        });

        return redirect()->route('accounting.expenses.index')->with('success', 'Expense created successfully.');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::all();
        $paymentAccounts = Account::whereIn('account_type', ['Asset', 'Liability'])->get();
        $expenseAccounts = Account::where('account_type', 'Expense')->get();
        $expenseAccountId = DB::table('journal_entries')->join('journal_entry_items', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->where('reference_type', 'Expense')->where('reference_id', $expense->id)->where('debit', '>', 0)->value('account_id');

        return view('accounting.expenses.edit', compact('expense', 'categories', 'paymentAccounts', 'expenseAccounts', 'expenseAccountId'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_account_id' => ['required', Rule::exists('accounts', 'id')->where('account_type', 'Expense')],
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'paid_from_account_id' => ['required', Rule::exists('accounts', 'id')->whereIn('account_type', ['Asset', 'Liability'])],
            'reference_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        DB::transaction(function () use ($expense, $data) {
            $expense = Expense::lockForUpdate()->findOrFail($expense->id);
            $this->journalService->deleteEntryByReference('Expense', $expense->id);
            $expense->update(collect($data)->except('expense_account_id')->all());
            $this->journalService->createEntry($expense->expense_date, 'Expense', $expense->id, $expense->expense_no, 'Expense: '.$expense->title, [
                ['account_id' => $data['expense_account_id'], 'debit' => $expense->amount, 'credit' => 0],
                ['account_id' => $expense->paid_from_account_id, 'debit' => 0, 'credit' => $expense->amount],
            ]);
            ActivityLog::log('Update', 'Expenses', 'Updated expense '.$expense->expense_no);
        });

        return redirect()->route('accounting.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            // Delete associated journal entry
            $this->journalService->deleteEntryByReference('Expense', $expense->id);

            // Delete expense
            $expense->delete();

            // Activity Log
            \DB::table('activity_logs')->insert([
                'user_id' => auth()->id(),
                'action' => 'Deleted Expense',
                'module' => 'Expenses',
                'description' => "Deleted expense {$expense->expense_no}",
                'created_at' => now(),
            ]);
        });

        return redirect()->route('accounting.expenses.index')->with('success', 'Expense deleted successfully.');
    }
}
