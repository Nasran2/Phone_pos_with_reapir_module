<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RepairReportController extends Controller
{
    public function __invoke(Request $request, string $report = 'jobs'): View|Response|BinaryFileResponse
    {
        $request->validate(['export' => ['nullable', 'in:pdf,excel']]);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $queryRequest = $report === 'revenue' ? $request->duplicate(Arr::except($request->query(), ['from', 'to'])) : $request;
        $query = app(RepairJobController::class)->query($queryRequest);
        if ($report === 'revenue' && ($request->filled('from') || $request->filled('to'))) {
            $query->whereHas('payments', function ($payments) use ($request) {
                if ($request->filled('from')) {
                    $payments->whereDate(DB::raw('COALESCE(cleared_at, payment_date)'), '>=', $request->input('from'));
                }
                if ($request->filled('to')) {
                    $payments->whereDate(DB::raw('COALESCE(cleared_at, payment_date)'), '<=', $request->input('to'));
                }
            });
        }
        $jobs = $query->with(['customer', 'device', 'repairStatus', 'technician', 'parts', 'charges', 'payments'])->latest()->get();
        $headers = [];
        $rows = [];
        switch ($report) {
            case 'parts':
                $headers = ['Job', 'Date', 'Store', 'Part', 'Variant', 'Status', 'Quantity', 'Cost', 'Selling price', 'Line total'];
                foreach ($jobs as $job) {
                    foreach ($job->parts as $part) {
                        $rows[] = [$job->job_no, $part->created_at->format('Y-m-d'), $job->store_id, $part->name_snapshot ?? $part->product_id, $part->variant_id, $part->status, $part->quantity, $part->cost_price, $part->selling_price, $part->line_total];
                    }
                }break;
            case 'technicians':
                $headers = ['Technician', 'Jobs', 'Completed', 'Delivered', 'Revenue', 'Outstanding'];
                foreach ($jobs->groupBy('primary_technician_id') as $group) {
                    $rows[] = [$group->first()->technician ? $group->first()->technician->name : 'Unassigned', $group->count(), $group->whereNotNull('completed_at')->count(), $group->whereNotNull('delivered_at')->count(), $group->sum('total_amount'), $group->sum('balance_amount')];
                }break;
            case 'statuses':
                $headers = ['Status', 'Jobs', 'Total charges', 'Received', 'Outstanding'];
                foreach ($jobs->groupBy('repair_status_id') as $group) {
                    $rows[] = [$group->first()->status_label, $group->count(), $group->sum('total_amount'), $group->sum('paid_amount'), $group->sum('balance_amount')];
                }break;
            case 'revenue':
                $headers = ['Job', 'Received on', 'Customer', 'Parts', 'Services', 'Discount', 'Tax', 'Invoice total', 'Cleared collections in period', 'Cheque hold in period', 'Current balance'];
                foreach ($jobs as $job) {
                    $payments = $job->payments->filter(fn ($payment) => (! $request->filled('from') || ($payment->cleared_at ?? $payment->payment_date)->toDateString() >= $request->input('from')) && (! $request->filled('to') || ($payment->cleared_at ?? $payment->payment_date)->toDateString() <= $request->input('to')));
                    $rows[] = [$job->job_no, $job->created_at->format('Y-m-d'), $job->customer->name, $job->parts_total, $job->labor_charge, $job->discount_amount, $job->tax_amount, $job->total_amount, $payments->where('status', 'cleared')->sum('amount'), $payments->where('status', 'hold')->sum('amount'), $job->balance_amount];
                }break;
            default:
                $headers = ['Job', 'Date', 'Customer', 'Phone', 'Device', 'IMEI / Serial', 'Status', 'Technician', 'Expected', 'Total', 'Received', 'Balance'];
                foreach ($jobs as $job) {
                    if ($report === 'outstanding' && (float) $job->balance_amount <= 0) {
                        continue;
                    }
                    $rows[] = [$job->job_no, $job->created_at->format('Y-m-d'), $job->customer->name, $job->customer->phone, trim($job->device?->brand.' '.$job->device?->model), $job->device ? ($job->device->imei_1 ?: $job->device->serial_number) : '', $job->status_label, $job->technician ? $job->technician->name : 'Unassigned', $job->expected_completion_date?->format('Y-m-d'), $job->total_amount, $job->paid_amount, $job->balance_amount];
                }
        }
        $title = 'Repair '.ucfirst($report).' Report';
        $filters = $request->only(['from', 'to', 'customer_id', 'store_id', 'status_id', 'technician_id', 'search', 'payment']);
        if ($request->input('export') === 'pdf') {
            return app(PdfExportService::class)->export($title, $headers, $rows, [], $filters);
        }
        if ($request->input('export') === 'excel') {
            return ExcelExportService::export($title, $headers, $rows, [], $filters);
        }

        return view('repairs.reports', array_merge(app(RepairJobController::class)->options(), compact('report', 'title', 'headers', 'rows')));
    }
}
