<x-layouts::app :title="__('Repair Status Board')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Status Board</h1>
        <p class="mt-1 text-sm text-slate-500">A fast operational board for front desk and repair bench follow-up.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($statuses as $status)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        @include('repairs.partials.status-badge', ['status' => $status])
                        <div class="mt-4 text-3xl font-black text-slate-900">{{ $counts[$status] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-black text-slate-900">Latest Status Changes</h2>
                </div>
                @include('repairs.partials.job-table', ['jobs' => $latest])
            </section>
        </div>
    </div>
</x-layouts::app>
