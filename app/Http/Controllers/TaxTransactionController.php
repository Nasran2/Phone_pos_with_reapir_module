<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxTransactionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('tax_transactions.view');

        $query = DB::table('tax_transactions');

        if ($request->filled('date')) {
            $query->whereDate('transaction_date', $request->date);
        }
        
        if ($request->filled('period_id')) {
            // Can be used if needed
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        if ($request->ajax()) {
            return view('tax.partials.daily_transactions', compact('transactions'));
        }

        return view('tax.transactions.index', compact('transactions'));
    }
}
