@php
    $statusStyles = [
        'Checked In' => 'bg-sky-50 text-sky-700 border-sky-200',
        'Diagnosis' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Waiting Approval' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Waiting Parts' => 'bg-orange-50 text-orange-700 border-orange-200',
        'Assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Under Repair' => 'bg-violet-50 text-violet-700 border-violet-200',
        'Quality Check' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Completed' => 'bg-teal-50 text-teal-700 border-teal-200',
        'Delivered' => 'bg-slate-100 text-slate-700 border-slate-200',
        'Cancelled' => 'bg-red-50 text-red-700 border-red-200',
        'Rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-extrabold {{ $statusStyles[$status] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
    {{ $status }}
</span>
