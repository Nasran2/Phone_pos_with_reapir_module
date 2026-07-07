<?php

$dir = __DIR__ . '/app/Http/Controllers';

$controllers = [
    'ChartOfAccountController' => <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request \$request)
    {
        \$query = Account::with('parent')->orderBy('account_code');
        
        if (\$request->filled('type')) {
            \$query->where('account_type', \$request->type);
        }
        if (\$request->filled('status')) {
            \$query->where('status', \$request->status == 'active' ? 1 : 0);
        }
        if (\$request->filled('search')) {
            \$query->where('account_name', 'like', '%' . \$request->search . '%')
                  ->orWhere('account_code', 'like', '%' . \$request->search . '%');
        }

        \$accounts = \$query->paginate(20)->withQueryString();
        return view('accounting.accounts.index', compact('accounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'account_code' => 'required|string|unique:accounts',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        \$validated['current_balance'] = \$validated['opening_balance'] ?? 0;
        \$validated['status'] = \$request->has('status');

        Account::create(\$validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account created successfully.');
    }

    public function destroy(Account \$account)
    {
        if (\$account->journalItems()->exists()) {
            return redirect()->route('accounting.accounts.index')->with('error', 'Cannot delete account with existing transactions.');
        }
        \$account->delete();
        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted successfully.');
    }
}
PHP
,
    'JournalEntryController' => <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JournalEntryController extends Controller
{
    protected \$journalService;

    public function __construct(JournalService \$journalService)
    {
        \$this->journalService = \$journalService;
    }

    public function index(Request \$request)
    {
        \$query = JournalEntry::with(['items.account', 'creator'])->latest();

        if (\$request->filled('date_filter')) {
            switch (\$request->date_filter) {
                case 'today':
                    \$query->whereDate('journal_date', Carbon::today());
                    break;
                case 'this_month':
                    \$query->whereMonth('journal_date', Carbon::now()->month)->whereYear('journal_date', Carbon::now()->year);
                    break;
                // Add more filters as needed
            }
        } else {
            \$query->whereDate('journal_date', Carbon::today());
        }

        \$journals = \$query->paginate(20)->withQueryString();
        \$accounts = Account::where('status', true)->get();

        return view('accounting.journal.index', compact('journals', 'accounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string'
        ]);

        \$totalDebit = collect(\$validated['items'])->sum('debit');
        \$totalCredit = collect(\$validated['items'])->sum('credit');

        if (round(\$totalDebit, 2) !== round(\$totalCredit, 2)) {
            return back()->with('error', 'Debits must equal Credits.')->withInput();
        }

        if (\$totalDebit == 0 && \$totalCredit == 0) {
            return back()->with('error', 'Cannot save empty journal entry.')->withInput();
        }

        \$this->journalService->createEntry(
            \$validated['journal_date'],
            'Manual',
            null,
            \$validated['reference_no'],
            \$validated['description'],
            \$validated['items'],
            false
        );

        return redirect()->route('accounting.journal-entries.index')->with('success', 'Journal entry created successfully.');
    }

    public function destroy(JournalEntry \$journal_entry)
    {
        if (\$journal_entry->is_auto_generated) {
            return back()->with('error', 'Cannot delete auto-generated journal entries.');
        }

        \$this->journalService->deleteEntry(\$journal_entry);
        return back()->with('success', 'Journal entry deleted.');
    }
}
PHP
,
    'LedgerBookController' => <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LedgerBookController extends Controller
{
    public function index(Request \$request)
    {
        \$accounts = Account::where('status', true)->orderBy('account_name')->get();
        \$selectedAccount = null;
        \$ledgerEntries = collect();
        \$openingBalance = 0;
        \$totalDebit = 0;
        \$totalCredit = 0;
        \$closingBalance = 0;

        if (\$request->has('account_id')) {
            \$selectedAccount = Account::find(\$request->account_id);
            if (\$selectedAccount) {
                \$query = JournalEntryItem::with('journal.creator')
                            ->where('account_id', \$selectedAccount->id)
                            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                            ->orderBy('journal_entries.journal_date', 'asc')
                            ->orderBy('journal_entries.id', 'asc')
                            ->select('journal_entry_items.*', 'journal_entries.journal_date as date', 'journal_entries.journal_no', 'journal_entries.reference_no', 'journal_entries.description as note');

                // Determine date range based on filter
                \$startDate = Carbon::today();
                \$endDate = Carbon::today();

                if (\$request->filled('date_filter')) {
                    switch (\$request->date_filter) {
                        case 'today':
                            break;
                        case 'this_week':
                            \$startDate = Carbon::now()->startOfWeek();
                            \$endDate = Carbon::now()->endOfWeek();
                            break;
                        case 'this_month':
                            \$startDate = Carbon::now()->startOfMonth();
                            \$endDate = Carbon::now()->endOfMonth();
                            break;
                        case 'custom':
                            if (\$request->filled('start_date')) \$startDate = Carbon::parse(\$request->start_date);
                            if (\$request->filled('end_date')) \$endDate = Carbon::parse(\$request->end_date);
                            break;
                    }
                } else {
                    \$request->merge(['date_filter' => 'today']);
                }

                // Calculate opening balance before start_date
                \$openingQuery = JournalEntryItem::where('account_id', \$selectedAccount->id)
                            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                            ->where('journal_entries.journal_date', '<', \$startDate->format('Y-m-d'));
                
                \$obDebit = \$openingQuery->sum('debit');
                \$obCredit = \$openingQuery->sum('credit');

                if (in_array(\$selectedAccount->account_type, ['Asset', 'Expense'])) {
                    \$openingBalance = \$selectedAccount->opening_balance + \$obDebit - \$obCredit;
                } else {
                    \$openingBalance = \$selectedAccount->opening_balance + \$obCredit - \$obDebit;
                }

                \$query->whereBetween('journal_entries.journal_date', [\$startDate->format('Y-m-d'), \$endDate->format('Y-m-d')]);
                
                \$ledgerEntries = \$query->get();
                \$totalDebit = \$ledgerEntries->sum('debit');
                \$totalCredit = \$ledgerEntries->sum('credit');

                if (in_array(\$selectedAccount->account_type, ['Asset', 'Expense'])) {
                    \$closingBalance = \$openingBalance + \$totalDebit - \$totalCredit;
                } else {
                    \$closingBalance = \$openingBalance + \$totalCredit - \$totalDebit;
                }
            }
        }

        return view('accounting.ledger.index', compact('accounts', 'selectedAccount', 'ledgerEntries', 'openingBalance', 'totalDebit', 'totalCredit', 'closingBalance'));
    }
}
PHP
,
    'CapitalDrawingController' => <<<PHP
<?php
namespace App\Http\Controllers;

use App\Models\CapitalDrawing;
use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CapitalDrawingController extends Controller
{
    protected \$journalService;

    public function __construct(JournalService \$journalService)
    {
        \$this->journalService = \$journalService;
    }

    public function index(Request \$request)
    {
        \$query = CapitalDrawing::with(['account', 'creator'])->latest();
        
        // Add basic filters
        if (\$request->filled('date_filter') && \$request->date_filter == 'today') {
            \$query->whereDate('transaction_date', Carbon::today());
        } elseif (!\$request->filled('date_filter')) {
            \$query->whereDate('transaction_date', Carbon::today());
        }

        \$transactions = \$query->paginate(20)->withQueryString();
        \$accounts = Account::whereIn('account_type', ['Asset'])->where('status', true)->get();
        \$equityAccounts = Account::where('account_type', 'Equity')->where('status', true)->get();

        return view('accounting.capital_drawings.index', compact('transactions', 'accounts', 'equityAccounts'));
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            'type' => 'required|in:capital,drawing',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'equity_account_id' => 'required|exists:accounts,id',
            'reference_no' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        \$validated['created_by'] = auth()->id();

        DB::transaction(function () use (\$validated, \$request) {
            \$record = CapitalDrawing::create([
                'type' => \$validated['type'],
                'transaction_date' => \$validated['transaction_date'],
                'amount' => \$validated['amount'],
                'account_id' => \$validated['account_id'],
                'reference_no' => \$validated['reference_no'],
                'notes' => \$validated['notes'],
                'created_by' => \$validated['created_by'],
            ]);

            \$items = [];
            if (\$validated['type'] == 'capital') {
                \$items = [
                    ['account_id' => \$validated['account_id'], 'debit' => \$validated['amount'], 'credit' => 0, 'notes' => 'Capital Received'],
                    ['account_id' => \$request->equity_account_id, 'debit' => 0, 'credit' => \$validated['amount'], 'notes' => 'Capital Recorded']
                ];
            } else {
                \$items = [
                    ['account_id' => \$request->equity_account_id, 'debit' => \$validated['amount'], 'credit' => 0, 'notes' => 'Drawings Taken'],
                    ['account_id' => \$validated['account_id'], 'debit' => 0, 'credit' => \$validated['amount'], 'notes' => 'Drawings Paid']
                ];
            }

            \$this->journalService->createEntry(
                \$validated['transaction_date'],
                ucfirst(\$validated['type']),
                \$record->id,
                \$validated['reference_no'],
                ucfirst(\$validated['type']) . ': ' . \$validated['notes'],
                \$items,
                true,
                \$validated['created_by']
            );
        });

        return back()->with('success', 'Transaction saved successfully.');
    }
}
PHP
];

foreach (\$controllers as \$name => \$content) {
    file_put_contents(\$dir . '/' . \$name . '.php', \$content);
}

echo "Controllers generated.\n";
