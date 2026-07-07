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
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $query = CapitalDrawing::with(['account', 'creator'])->latest();
        
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('transaction_date', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('transaction_date', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('transaction_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('transaction_date', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('transaction_date', Carbon::now()->month)->whereYear('transaction_date', Carbon::now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('transaction_date', Carbon::now()->subMonth()->month)->whereYear('transaction_date', Carbon::now()->subMonth()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        } else {
            $query->whereDate('transaction_date', Carbon::today());
            $request->merge(['date_filter' => 'today']);
        }

        if ($request->filled('payment_account_id')) {
            $query->where('account_id', $request->payment_account_id);
        }

        $transactions = $query->paginate(20)->withQueryString();
        $accounts = Account::whereIn('account_type', ['Asset'])->where('status', true)->get();
        $equityAccounts = Account::where('account_type', 'Equity')->where('status', true)->get();

        return view('accounting.capital_drawings.index', compact('transactions', 'accounts', 'equityAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:capital,drawing',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'equity_account_id' => 'required|exists:accounts,id',
            'reference_no' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        DB::transaction(function () use ($validated, $request) {
            $record = CapitalDrawing::create([
                'type' => $validated['type'],
                'transaction_date' => $validated['transaction_date'],
                'amount' => $validated['amount'],
                'account_id' => $validated['account_id'],
                'reference_no' => $validated['reference_no'],
                'notes' => $validated['notes'],
                'created_by' => $validated['created_by'],
            ]);

            $items = [];
            if ($validated['type'] == 'capital') {
                $items = [
                    ['account_id' => $validated['account_id'], 'debit' => $validated['amount'], 'credit' => 0, 'notes' => 'Capital Received'],
                    ['account_id' => $request->equity_account_id, 'debit' => 0, 'credit' => $validated['amount'], 'notes' => 'Capital Recorded']
                ];
            } else {
                $items = [
                    ['account_id' => $request->equity_account_id, 'debit' => $validated['amount'], 'credit' => 0, 'notes' => 'Drawings Taken'],
                    ['account_id' => $validated['account_id'], 'debit' => 0, 'credit' => $validated['amount'], 'notes' => 'Drawings Paid']
                ];
            }

            $this->journalService->createEntry(
                $validated['transaction_date'],
                ucfirst($validated['type']),
                $record->id,
                $validated['reference_no'],
                ucfirst($validated['type']) . ': ' . $validated['notes'],
                $items,
                true,
                $validated['created_by']
            );
        });

        return back()->with('success', ucfirst($validated['type']) . ' transaction saved successfully.');
    }
}
