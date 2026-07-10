<x-layouts::app :title="__('Repair Queue')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Queue</h1>
        <p class="mt-1 text-sm text-slate-500">Kanban-style view of work moving through the repair bench.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
                @foreach($columns as $column)
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            @include('repairs.partials.status-badge', ['status' => $column])
                            <span class="text-sm font-black text-slate-800">{{ ($jobs[$column] ?? collect())->count() }}</span>
                        </div>
                        <div class="space-y-3 p-4">
                            @forelse(($jobs[$column] ?? collect()) as $job)
                                <a href="{{ route('repairs.jobs.show', $job->id) }}" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:border-blue-200 hover:bg-blue-50">
                                    <div class="font-black text-slate-900">{{ $job->job_no }}</div>
                                    <div class="mt-1 text-sm font-bold text-slate-700">{{ $job->customer_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $job->brand }} {{ $job->model }} / {{ $job->technician_name ?: 'Unassigned' }}</div>
                                </a>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-xs font-bold text-slate-400">No jobs</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts::app>
