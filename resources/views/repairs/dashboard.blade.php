<x-layouts::app :title="__('Repair Dashboard')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Dashboard / Repair Management</nav>
                <h1 class="mt-2 text-2xl font-black text-slate-900">Repair Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Live service counters, repair queues, technician load, and today revenue.</p>
            </div>
            @if(auth()->user()->hasPermission('repair.create'))
                <a href="{{ route('repairs.jobs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">New Check-In</a>
            @endif
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['Today Check-Ins', $stats['today_checkins'], 'bg-blue-50 text-blue-700 border-blue-200'],
                    ['Pending Repairs', $stats['pending_repairs'], 'bg-amber-50 text-amber-700 border-amber-200'],
                    ['Under Repair', $stats['under_repair'], 'bg-violet-50 text-violet-700 border-violet-200'],
                    ['Ready / Delivered Today', $stats['completed_today'] . ' / ' . $stats['delivered_today'], 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                    ['Today Repair Income', \App\Support\SystemSettings::money((float) $stats['repair_income']), 'bg-cyan-50 text-cyan-700 border-cyan-200'],
                ] as $card)
                    <div class="rounded-2xl border {{ $card[2] }} bg-white p-5 shadow-sm">
                        <div class="text-xs font-extrabold uppercase tracking-wide opacity-80">{{ $card[0] }}</div>
                        <div class="mt-3 text-2xl font-black">{{ $card[1] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Recent Repair Job Cards</h2>
                            <p class="text-xs text-slate-500">Newest customer devices checked into service.</p>
                        </div>
                        <a href="{{ route('repairs.jobs.index') }}" class="text-xs font-extrabold text-blue-600 hover:text-blue-700">View all</a>
                    </div>
                    @include('repairs.partials.job-table', ['jobs' => $recentJobs])
                </section>

                <section class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-black text-slate-900">Status Mix</h2>
                        <div class="mt-4 space-y-3">
                            @foreach($statusChart as $status => $total)
                                <div class="flex items-center justify-between gap-3">
                                    @include('repairs.partials.status-badge', ['status' => $status])
                                    <span class="text-sm font-black text-slate-900">{{ $total }}</span>
                                </div>
                            @endforeach
                            @if($statusChart->isEmpty())
                                <div class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">No repair statuses yet.</div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-black text-slate-900">Technician Load</h2>
                        <div class="mt-4 space-y-3">
                            @forelse($technicianPerformance as $tech)
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                                    <span class="text-sm font-bold text-slate-700">{{ $tech->name }}</span>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700">{{ $tech->total }}</span>
                                </div>
                            @empty
                                <div class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-500">No technicians added.</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts::app>
