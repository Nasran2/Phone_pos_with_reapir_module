<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use App\Services\PdfExportService;

class LedgerController extends Controller
{
    protected $pdfService;

    public function __construct(PdfExportService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $accounts = Account::where('is_active', 1)->orderBy('code')->get();
        $selectedAccount = null;
        $ledgerEntries = collect();
        $openingBalance = 0;

        if ($request->has('account_id')) {
            $selectedAccount = Account::find($request->account_id);
            if ($selectedAccount) {
                $query = LedgerEntry::with('journalEntry.user')
                            ->where('chart_of_account_id', $selectedAccount->id)
                            ->join('journal_entries', 'ledger_entries.journal_entry_id', '=', 'journal_entries.id')
                            ->orderBy('journal_entries.entry_date', 'asc')
                            ->orderBy('journal_entries.id', 'asc')
                            ->select('ledger_entries.*', 'journal_entries.entry_date as date', 'journal_entries.reference_no', 'journal_entries.narration as note');

                if ($request->has('start_date') && $request->start_date) {
                    $openingQuery = LedgerEntry::where('chart_of_account_id', $selectedAccount->id)
                            ->join('journal_entries', 'ledger_entries.journal_entry_id', '=', 'journal_entries.id')
                            ->where('journal_entries.entry_date', '<', $request->start_date);
                    
                    if (in_array(strtolower($selectedAccount->type), ['asset', 'expense'])) {
                        $openingBalance = $openingQuery->sum('debit') - $openingQuery->sum('credit');
                    } else {
                        $openingBalance = $openingQuery->sum('credit') - $openingQuery->sum('debit');
                    }

                    $query->where('journal_entries.entry_date', '>=', $request->start_date);
                }

                if ($request->has('end_date') && $request->end_date) {
                    $query->where('journal_entries.entry_date', '<=', $request->end_date);
                }

                $ledgerEntries = $query->get();
            }
        }

        if ($request->query('type') === 'pdf' && $selectedAccount) {
            $headers = ['Date', 'Journal No', 'Reference', 'Description', 'Debit (Rs)', 'Credit (Rs)', 'Balance'];
            $rows = [];
            $rows[] = ['-', '-', '-', 'Opening Balance', '-', '-', number_format($openingBalance, 2)];
            
            $runningBalance = $openingBalance;
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($ledgerEntries as $entry) {
                if (in_array($selectedAccount->account_type, ['Asset', 'Expense'])) {
                    $runningBalance = $runningBalance + $entry->debit - $entry->credit;
                } else {
                    $runningBalance = $runningBalance + $entry->credit - $entry->debit;
                }
                $totalDebit += $entry->debit;
                $totalCredit += $entry->credit;
                
                $rows[] = [
                    \Carbon\Carbon::parse($entry->date)->format('M d, Y'),
                    $entry->journal_no ?? '-',
                    $entry->reference_no ?? '-',
                    $entry->note,
                    $entry->debit > 0 ? number_format($entry->debit, 2) : '-',
                    $entry->credit > 0 ? number_format($entry->credit, 2) : '-',
                    number_format($runningBalance, 2)
                ];
            }
            
            $totals = [
                'Date' => 'Closing Balance',
                'Journal No' => '',
                'Reference' => '',
                'Description' => '',
                'Debit (Rs)' => number_format($totalDebit, 2),
                'Credit (Rs)' => number_format($totalCredit, 2),
                'Balance' => number_format($runningBalance, 2),
            ];

            return $this->pdfService->export(
                $selectedAccount->account_name . ' - Ledger Book', 
                $headers, 
                $rows, 
                $totals, 
                ['Date Filter' => $request->start_date . ' to ' . $request->end_date]
            );
        }

        return view('accounting.ledger.index', compact('accounts', 'selectedAccount', 'ledgerEntries', 'openingBalance'));
    }
}