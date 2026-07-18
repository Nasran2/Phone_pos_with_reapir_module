<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TaxReportController extends Controller
{
    public function index()
    {
        $this->authorize('tax_reports.view');
        return view('tax.reports.index');
    }

    public function vatReturn(Request $request)
    {
        $this->authorize('tax_reports.view');
        app(\App\Services\TaxBalanceService::class)->autoGeneratePeriods();
        $periods = \App\Models\TaxPeriod::orderBy('start_date', 'desc')->paginate(12);
        return view('tax.reports.vat_return', compact('periods'));
    }

    public function paymentRegister(Request $request)
    {
        $this->authorize('tax_reports.view');
        $payments = \App\Models\TaxPayment::with('allocations.period')->orderBy('payment_date', 'desc')->paginate(15);
        return view('tax.reports.payment_register', compact('payments'));
    }
}
