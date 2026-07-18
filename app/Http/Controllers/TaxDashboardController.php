<?php

namespace App\Http\Controllers;

use App\Models\TaxPeriod;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('tax_dashboard.view');

        // Auto-generate missing months
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();

        // Determine date range from request
        $dateFilter = $request->input('date_filter', 'this_month');
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        switch ($dateFilter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday()->endOfDay();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'last_year':
                $startDate = Carbon::now()->subYear()->startOfYear();
                $endDate = Carbon::now()->subYear()->endOfYear();
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = Carbon::parse($request->start_date)->startOfDay();
                    $endDate = Carbon::parse($request->end_date)->endOfDay();
                }
                break;
            case 'all_time':
                $startDate = Carbon::create(2000, 1, 1);
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'this_month':
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
        }

        $activePeriod = TaxPeriod::where('start_date', '<=', $startDate)
                                 ->where('end_date', '>=', $startDate)
                                 ->first();
                                 
        if (!$activePeriod) {
            $activePeriod = TaxPeriod::where('period_status', 'Open')->orderBy('start_date', 'asc')->first();
        }
        
        // Calculate filtered output and input VAT dynamically for the selected date range
        $filteredOutput = \Illuminate\Support\Facades\DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('input_or_output', 'output')
            ->sum('tax_amount');
            
        $filteredInput = \Illuminate\Support\Facades\DB::table('tax_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('input_or_output', 'input')
            ->sum('recoverable_amount');
        
        $stats = [
            'payable_balance' => TaxPeriod::where('payment_status', '!=', 'Paid')->sum('closing_payable_balance'),
            'credit_balance' => TaxPeriod::where('period_status', 'Open')->sum('opening_credit_balance'),
            'total_output' => $filteredOutput,
            'total_input' => $filteredInput,
            'net_vat' => $filteredOutput - $filteredInput,
        ];
        
        return view('tax.dashboard', compact('stats', 'activePeriod', 'dateFilter', 'startDate', 'endDate'));
    }
}
