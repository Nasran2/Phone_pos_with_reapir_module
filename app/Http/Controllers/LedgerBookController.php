<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LedgerBookController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::where('status', true)->orderBy('account_name')->get();
        $selectedAccount = null;
        $ledgerEntries = collect();
        $openingBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $closingBalance = 0;

        if ($request->has('account_id') && $request->account_id) {
            $selectedAccount = Account::find($request->account_id);
            if ($selectedAccount) {
                $query = JournalEntryItem::with('journal.creator')
                            ->where('account_id', $selectedAccount->id)
                            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                            ->orderBy('journal_entries.journal_date', 'asc')
                            ->orderBy('journal_entries.id', 'asc')
                            ->select('journal_entry_items.*', 'journal_entries.journal_date as date', 'journal_entries.journal_no', 'journal_entries.reference_no', 'journal_entries.description as note');

                // Determine date range based on filter
                $startDate = Carbon::today();
                $endDate = Carbon::today();

                if ($request->filled('date_filter')) {
                    switch ($request->date_filter) {
                        case 'today':
                            break;
                        case 'yesterday':
                            $startDate = Carbon::yesterday();
                            $endDate = Carbon::yesterday();
                            break;
                        case 'this_week':
                            $startDate = Carbon::now()->startOfWeek();
                            $endDate = Carbon::now()->endOfWeek();
                            break;
                        case 'last_week':
                            $startDate = Carbon::now()->subWeek()->startOfWeek();
                            $endDate = Carbon::now()->subWeek()->endOfWeek();
                            break;
                        case 'this_month':
                            $startDate = Carbon::now()->startOfMonth();
                            $endDate = Carbon::now()->endOfMonth();
                            break;
                        case 'last_month':
                            $startDate = Carbon::now()->subMonth()->startOfMonth();
                            $endDate = Carbon::now()->subMonth()->endOfMonth();
                            break;
                        case 'custom':
                            if ($request->filled('start_date')) $startDate = Carbon::parse($request->start_date);
                            if ($request->filled('end_date')) $endDate = Carbon::parse($request->end_date);
                            break;
                    }
                } else {
                    $request->merge(['date_filter' => 'today']);
                }

                // Calculate opening balance before start_date
                $openingQuery = JournalEntryItem::where('account_id', $selectedAccount->id)
                            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
                            ->where('journal_entries.journal_date', '<', $startDate->format('Y-m-d'));
                
                $obDebit = $openingQuery->sum('debit');
                $obCredit = $openingQuery->sum('credit');

                if (in_array($selectedAccount->account_type, ['Asset', 'Expense'])) {
                    $openingBalance = $selectedAccount->opening_balance + $obDebit - $obCredit;
                } else {
                    $openingBalance = $selectedAccount->opening_balance + $obCredit - $obDebit;
                }

                $query->whereBetween('journal_entries.journal_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                
                $ledgerEntries = $query->get();
                $totalDebit = $ledgerEntries->sum('debit');
                $totalCredit = $ledgerEntries->sum('credit');

                if (in_array($selectedAccount->account_type, ['Asset', 'Expense'])) {
                    $closingBalance = $openingBalance + $totalDebit - $totalCredit;
                } else {
                    $closingBalance = $openingBalance + $totalCredit - $totalDebit;
                }
            }
        } else {
            $request->merge(['date_filter' => 'today']);
        }

        return view('accounting.ledger.index', compact('accounts', 'selectedAccount', 'ledgerEntries', 'openingBalance', 'totalDebit', 'totalCredit', 'closingBalance'));
    }
}
