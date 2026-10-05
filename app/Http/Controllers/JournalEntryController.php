<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = JournalEntry::with(['items.account', 'creator'])->latest();

        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('journal_date', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('journal_date', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('journal_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('journal_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('journal_date', Carbon::now()->month)->whereYear('journal_date', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('journal_date', Carbon::now()->subMonth()->month)->whereYear('journal_date', Carbon::now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $query->whereBetween('journal_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        } else {
            $query->whereDate('journal_date', Carbon::today());
            $request->merge(['date_filter' => 'today']);
        }

        $journals = $query->paginate(20)->withQueryString();
        $accounts = Account::available()->where('status', true)->get();

        return view('accounting.journal.index', compact('journals', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $totalDebit = collect($validated['items'])->sum('debit');
        $totalCredit = collect($validated['items'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->with('error', 'Debits must equal Credits.')->withInput();
        }

        if ($totalDebit == 0 && $totalCredit == 0) {
            return back()->with('error', 'Cannot save empty journal entry.')->withInput();
        }

        $this->journalService->createEntry(
            $validated['journal_date'],
            'Manual',
            null,
            $validated['reference_no'],
            $validated['description'],
            $validated['items'],
            false
        );

        return redirect()->route('accounting.journal-entries.index')->with('success', 'Journal entry created successfully.');
    }

    public function destroy(JournalEntry $journal_entry)
    {
        if ($journal_entry->is_auto_generated) {
            return back()->with('error', 'Cannot delete auto-generated journal entries.');
        }

        $this->journalService->deleteEntry($journal_entry);

        return back()->with('success', 'Journal entry deleted.');
    }
}
