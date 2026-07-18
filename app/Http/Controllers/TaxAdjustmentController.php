<?php

namespace App\Http\Controllers;

use App\Models\TaxAdjustment;
use App\Models\TaxPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxAdjustmentController extends Controller
{
    public function index()
    {
        $this->authorize('tax_management.view');
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();
        $adjustments = TaxAdjustment::with(['period', 'creator', 'approver'])->orderBy('created_at', 'desc')->paginate(15);
        return view('tax.adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $this->authorize('tax_adjustments.create');
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();
        $periods = TaxPeriod::where('period_status', 'Open')->orderBy('start_date', 'asc')->get();
        return view('tax.adjustments.create', compact('periods'));
    }

    public function store(Request $request)
    {
        $this->authorize('tax_adjustments.create');
        
        $validated = $request->validate([
            'tax_period_id' => 'required|exists:tax_periods,id',
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|string',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
        ]);
        
        DB::transaction(function () use ($validated) {
            TaxAdjustment::create([
                'tax_period_id' => $validated['tax_period_id'],
                'adjustment_date' => $validated['adjustment_date'],
                'adjustment_type' => $validated['adjustment_type'],
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
                'status' => 'Draft',
                'created_by' => auth()->id(),
            ]);
        });
        
        return redirect()->route('tax.adjustments.index')->with('success', 'Tax adjustment drafted successfully.');
    }
    
    public function approve(TaxAdjustment $adjustment)
    {
        $this->authorize('tax_adjustments.approve');
        
        if ($adjustment->status !== 'Draft') {
            return back()->with('error', 'Only draft adjustments can be approved.');
        }
        
        DB::transaction(function () use ($adjustment) {
            $adjustment->update([
                'status' => 'Approved',
                'approved_by' => auth()->id(),
            ]);
            
            app(\App\Services\TaxBalanceService::class)->calculatePeriodLiability($adjustment->period);
        });
        
        return back()->with('success', 'Adjustment approved successfully.');
    }
}
