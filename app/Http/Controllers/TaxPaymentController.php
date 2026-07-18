<?php

namespace App\Http\Controllers;

use App\Models\TaxPayment;
use App\Models\TaxPeriod;
use App\Models\TaxPaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxPaymentController extends Controller
{
    public function index()
    {
        $this->authorize('tax_payments.view');
        $payments = TaxPayment::with(['enterer', 'approver'])->orderBy('created_at', 'desc')->paginate(15);
        return view('tax.payments.index', compact('payments'));
    }

    public function create()
    {
        $this->authorize('tax_payments.create');
        // Auto-generate missing months first
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();
        
        $periods = TaxPeriod::where('closing_payable_balance', '>', 0)
                            ->orderBy('start_date', 'asc')
                            ->get();
        return view('tax.payments.create', compact('periods'));
    }

    public function store(Request $request)
    {
        $this->authorize('tax_payments.create');
        
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'tax_period_id' => 'required|exists:tax_periods,id',
            'total_amount' => 'required|numeric|min:0.01',
            'bank_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        DB::transaction(function () use ($validated) {
            $payment = TaxPayment::create([
                'payment_number' => 'TPAY-' . date('Ymd') . '-' . Str::random(4),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $validated['total_amount'],
                'payment_method' => $validated['payment_method'],
                'bank_reference' => $validated['bank_reference'],
                'notes' => $validated['notes'],
                'status' => 'Draft',
                'entered_by' => auth()->id(),
            ]);
            
            TaxPaymentAllocation::create([
                'tax_payment_id' => $payment->id,
                'tax_period_id' => $validated['tax_period_id'],
                'allocated_amount' => $validated['total_amount'],
                'allocation_type' => 'Period VAT',
            ]);
        });
        
        return redirect()->route('tax.payments.index')->with('success', 'Tax payment drafted successfully.');
    }
    
    public function show(TaxPayment $payment)
    {
        $this->authorize('tax_payments.view');
        $payment->load(['enterer', 'approver', 'allocations.period']);
        return view('tax.payments.show', compact('payment'));
    }

    public function approve(TaxPayment $payment)
    {
        $this->authorize('tax_payments.approve');
        
        if ($payment->status !== 'Draft') {
            return back()->with('error', 'Only draft payments can be approved.');
        }
        
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'Approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            // Re-calculate the period liability
            foreach ($payment->allocations as $allocation) {
                app(\App\Services\TaxBalanceService::class)->calculatePeriodLiability($allocation->period);
            }
        });
        
        return back()->with('success', 'Payment approved successfully.');
    }
    
    public function reverse(Request $request, TaxPayment $payment)
    {
        $this->authorize('tax_payments.reverse');
        
        if ($payment->status !== 'Approved') {
            return back()->with('error', 'Only approved payments can be reversed.');
        }
        
        $request->validate(['reversal_reason' => 'required|string|min:5']);
        
        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'Reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $request->reversal_reason,
            ]);
            
            // Re-calculate the period liability
            foreach ($payment->allocations as $allocation) {
                app(\App\Services\TaxBalanceService::class)->calculatePeriodLiability($allocation->period);
            }
        });
        
        return back()->with('success', 'Payment reversed successfully.');
    }
}
