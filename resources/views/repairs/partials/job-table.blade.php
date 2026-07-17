<div class="overflow-x-auto">
    <table class="w-full min-w-[980px] text-left">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                <th class="px-5 py-4">Job</th>
                <th class="px-5 py-4">Customer</th>
                <th class="px-5 py-4">Device</th>
                <th class="px-5 py-4">Status</th>
                <th class="px-5 py-4">Technician</th>
                <th class="px-5 py-4 text-right">Total</th>
                <th class="px-5 py-4 text-right">Balance</th>
                <th class="px-5 py-4 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @forelse($jobs as $job)
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-4">
                        <div class="font-extrabold text-slate-900">{{ $job->job_no }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ optional($job->created_at ? \Carbon\Carbon::parse($job->created_at) : null)->format('Y-m-d h:i A') }}</div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="font-bold text-slate-800">{{ $job->customer_name }}</div>
                        <div class="text-xs text-slate-500">{{ $job->customer_phone }}</div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="font-bold text-slate-800">{{ trim(($job->brand ?? '') . ' ' . ($job->model ?? '')) ?: 'Device not set' }}</div>
                        <div class="text-xs text-slate-500">{{ $job->device_type }} @if($job->imei_1) / {{ $job->imei_1 }} @endif</div>
                    </td>
                    <td class="px-5 py-4">@include('repairs.partials.status-badge', ['status' => $job->status])</td>
                    <td class="px-5 py-4 text-slate-600">{{ $job->technician_name ?: 'Unassigned' }}</td>
                    <td class="px-5 py-4 text-right font-bold text-slate-700">{{ \App\Support\SystemSettings::money((float) ($job->total_amount ?: $job->estimated_cost)) }}</td>
                    <td class="px-5 py-4 text-right font-extrabold text-slate-900">{{ \App\Support\SystemSettings::money((float) $job->balance_amount) }}</td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('repairs.jobs.show', $job->id) }}" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-extrabold text-blue-700 hover:bg-blue-100">
                            Open
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center">
                        <div class="mx-auto max-w-sm rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8">
                            <div class="text-sm font-extrabold text-slate-700">No repair jobs found</div>
                            <div class="mt-1 text-xs text-slate-500">New check-ins will appear here as job cards.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
