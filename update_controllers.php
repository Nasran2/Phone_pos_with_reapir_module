<?php

// 1. AccountController
$accountController = <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        \$accounts = Account::orderBy('code')->paginate(50);
        return view('accounting.accounts.index', compact('accounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'code' => 'required|string|unique:chart_of_accounts',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'is_active' => 'boolean'
        ]);
        
        \$validated['group_id'] = 1; // Default
        \$validated['balance'] = 0;

        Account::create(\$validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account created successfully.');
    }

    public function update(Request \$request, Account \$account)
    {
        \$validated = \$request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code,' . \$account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'is_active' => 'boolean'
        ]);

        \$account->update(\$validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account \$account)
    {
        if (\$account->ledger_entries()->exists() || \App\Models\Expense::where('chart_of_account_id', \$account->id)->exists()) {
            return redirect()->route('accounting.accounts.index')->with('error', 'Cannot delete account with existing transactions.');
        }
        \$account->delete();
        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted successfully.');
    }
}
PHP;
file_put_contents(__DIR__ . '/app/Http/Controllers/AccountController.php', $accountController);


// 2. ExpenseController
$expenseController = <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index()
    {
        \$expenses = Expense::with(['expenseCategory', 'paymentAccount', 'user'])->latest()->paginate(20);
        \$expenseCategories = ExpenseCategory::all();
        \$paymentAccounts = Account::whereIn('type', ['Asset', 'Liability'])->where('is_active', 1)->get();
        return view('accounting.expenses.index', compact('expenses', 'expenseCategories', 'paymentAccounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]);

        \$validated['created_by'] = auth()->id();
        if (empty(\$validated['reference_no'])) {
            \$validated['reference_no'] = 'EXP-' . strtoupper(uniqid());
        }

        DB::transaction(function () use (\$validated) {
            \$expense = Expense::create(\$validated);

            // Double Entry
            \$journal = JournalEntry::create([
                'reference_no' => \$expense->reference_no,
                'entry_date' => \$expense->expense_date,
                'narration' => 'Expense: ' . \$expense->note,
                'created_by' => auth()->id()
            ]);

            // Assuming ExpenseCategory needs a mapping to an Account, or we just credit the payment account and debit a default expense account
            // For now, debiting the payment account as expense? No, payment is Credit.
            // Let's create an expense account for the category if it doesn't exist.
            \$category = ExpenseCategory::find(\$expense->expense_category_id);
            \$expAcc = Account::firstOrCreate(
                ['code' => '500' . \$category->id],
                ['name' => \$category->name, 'type' => 'Expense', 'group_id' => 1, 'balance' => 0]
            );

            // Debit Expense
            \$journal->details()->create([
                'chart_of_account_id' => \$expAcc->id,
                'entry_date' => \$expense->expense_date,
                'debit' => \$expense->amount,
                'credit' => 0,
                'balance' => 0
            ]);

            // Credit Asset (Payment)
            \$journal->details()->create([
                'chart_of_account_id' => \$expense->chart_of_account_id,
                'entry_date' => \$expense->expense_date,
                'debit' => 0,
                'credit' => \$expense->amount,
                'balance' => 0
            ]);
        });

        return redirect()->route('accounting.expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function destroy(Expense \$expense)
    {
        DB::transaction(function () use (\$expense) {
            JournalEntry::where('reference_no', \$expense->reference_no)->delete();
            \$expense->delete();
        });
        return redirect()->route('accounting.expenses.index')->with('success', 'Expense deleted.');
    }
}
PHP;
file_put_contents(__DIR__ . '/app/Http/Controllers/ExpenseController.php', $expenseController);


// 3. JournalEntryController
$journalController = <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function index()
    {
        \$journals = JournalEntry::with('details.account', 'user')->latest()->paginate(20);
        \$accounts = Account::where('is_active', 1)->orderBy('code')->get();
        return view('accounting.journal.index', compact('journals', 'accounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'entry_date' => 'required|date',
            'narration' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'nullable|numeric|min:0',
            'details.*.credit' => 'nullable|numeric|min:0',
        ]);

        \$totalDebit = collect(\$validated['details'])->sum('debit');
        \$totalCredit = collect(\$validated['details'])->sum('credit');

        if (round(\$totalDebit, 2) !== round(\$totalCredit, 2)) {
            return back()->with('error', 'Debits must equal Credits.');
        }

        DB::transaction(function () use (\$validated) {
            \$journal = JournalEntry::create([
                'reference_no' => 'JE-' . strtoupper(uniqid()),
                'entry_date' => \$validated['entry_date'],
                'narration' => \$validated['narration'],
                'created_by' => auth()->id()
            ]);

            foreach (\$validated['details'] as \$detail) {
                if ((\$detail['debit'] > 0 || \$detail['credit'] > 0) && !empty(\$detail['chart_of_account_id'])) {
                    \$journal->details()->create([
                        'chart_of_account_id' => \$detail['chart_of_account_id'],
                        'entry_date' => \$validated['entry_date'],
                        'debit' => \$detail['debit'] ?? 0,
                        'credit' => \$detail['credit'] ?? 0,
                        'balance' => 0
                    ]);
                }
            }
        });

        return redirect()->route('accounting.journal-entries.index')->with('success', 'Journal entry created.');
    }
}
PHP;
file_put_contents(__DIR__ . '/app/Http/Controllers/JournalEntryController.php', $journalController);


// 4. LedgerController
$ledgerController = <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request \$request)
    {
        \$accounts = Account::where('is_active', 1)->orderBy('code')->get();
        \$selectedAccount = null;
        \$ledgerEntries = collect();
        \$openingBalance = 0;

        if (\$request->has('account_id')) {
            \$selectedAccount = Account::find(\$request->account_id);
            if (\$selectedAccount) {
                \$query = LedgerEntry::with('journalEntry.user')
                            ->where('chart_of_account_id', \$selectedAccount->id)
                            ->join('journal_entries', 'ledger_entries.journal_entry_id', '=', 'journal_entries.id')
                            ->orderBy('journal_entries.entry_date', 'asc')
                            ->orderBy('journal_entries.id', 'asc')
                            ->select('ledger_entries.*', 'journal_entries.entry_date as date', 'journal_entries.reference_no', 'journal_entries.narration as note');

                if (\$request->has('start_date') && \$request->start_date) {
                    \$openingQuery = LedgerEntry::where('chart_of_account_id', \$selectedAccount->id)
                            ->join('journal_entries', 'ledger_entries.journal_entry_id', '=', 'journal_entries.id')
                            ->where('journal_entries.entry_date', '<', \$request->start_date);
                    
                    if (in_array(strtolower(\$selectedAccount->type), ['asset', 'expense'])) {
                        \$openingBalance = \$openingQuery->sum('debit') - \$openingQuery->sum('credit');
                    } else {
                        \$openingBalance = \$openingQuery->sum('credit') - \$openingQuery->sum('debit');
                    }

                    \$query->where('journal_entries.entry_date', '>=', \$request->start_date);
                }

                if (\$request->has('end_date') && \$request->end_date) {
                    \$query->where('journal_entries.entry_date', '<=', \$request->end_date);
                }

                \$ledgerEntries = \$query->get();
            }
        }

        return view('accounting.ledger.index', compact('accounts', 'selectedAccount', 'ledgerEntries', 'openingBalance'));
    }
}
PHP;
file_put_contents(__DIR__ . '/app/Http/Controllers/LedgerController.php', $ledgerController);

echo "Controllers updated to match existing schema.\n";
