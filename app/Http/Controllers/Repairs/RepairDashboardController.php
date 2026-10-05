<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Models\RepairJob;
use App\Models\RepairNotification;
use App\Models\RepairPayment;
use App\Models\RepairStatus;
use App\Models\RepairTechnician;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['store_id' => ['nullable', 'exists:stores,id']]);
        $query = RepairJob::query()->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->input('store_id')));
        $counts = (clone $query)->selectRaw('repair_status_id, COUNT(*) as jobs')->groupBy('repair_status_id')->pluck('jobs', 'repair_status_id');
        $statuses = RepairStatus::orderBy('sort_order')->get();
        $open = (clone $query)->whereNull('completed_at')->whereNull('delivered_at')->whereHas('repairStatus', fn ($s) => $s->where('is_cancelled', false));
        $overdue = (clone $open)->whereDate('expected_completion_date', '<', today())->count();
        $balance = (clone $query)->sum('balance_amount');
        $payments = RepairPayment::where('status', 'cleared')->whereDate(DB::raw('COALESCE(cleared_at, payment_date)'), today())->whereIn('repair_job_id', (clone $query)->select('id'))->sum('amount');
        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $trend[] = ['date' => $date->format('M j'), 'jobs' => (clone $query)->whereDate('created_at', $date)->count(), 'revenue' => RepairPayment::where('status', 'cleared')->whereDate(DB::raw('COALESCE(cleared_at, payment_date)'), $date)->whereIn('repair_job_id', (clone $query)->select('id'))->sum('amount')];
        }
        $workload = RepairTechnician::withCount(['jobs' => fn ($q) => $q->whereIn('id', (clone $open)->select('id'))])->orderByDesc('jobs_count')->get();
        $recent = (clone $query)->with(['customer', 'device', 'repairStatus', 'technician'])->latest()->limit(8)->get();
        $notifications = RepairNotification::where('channel', 'Internal')->whereIn('repair_job_id', (clone $query)->select('id'))->latest()->limit(8)->get();

        return view('repairs.dashboard', compact('statuses', 'counts', 'overdue', 'balance', 'payments', 'trend', 'workload', 'recent', 'notifications') + ['stores' => Store::orderBy('name')->get()]);
    }
}
