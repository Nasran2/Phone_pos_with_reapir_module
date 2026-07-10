<x-layouts::app :title="__('Repair Reports')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Reports</h1>
        <p class="mt-1 text-sm text-slate-500">Income, parts value, pending workload, delayed jobs, and technician performance.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                @foreach([
                    ['Repair Income', \App\Support\SystemSettings::money((float) $summary['income']), 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                    ['Parts Value', \App\Support\SystemSettings::money((float) $summary['parts']), 'bg-blue-50 text-blue-800 border-blue-200'],
                    ['Pending Jobs', $summary['pending'], 'bg-amber-50 text-amber-800 border-amber-200'],
                    ['Delayed Jobs', $summary['delayed'], 'bg-red-50 text-red-800 border-red-200'],
                ] as $card)
                    <div class="rounded-2xl border {{ $card[2] }} bg-white p-5 shadow-sm">
                        <div class="text-xs font-black uppercase tracking-wide opacity-80">{{ $card[0] }}</div>
                        <div class="mt-3 text-2xl font-black">{{ $card[1] }}</div>
                    </div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-black text-slate-900">Technician Performance</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-4">Technician</th>
                                <th class="px-5 py-4 text-right">Jobs</th>
                                <th class="px-5 py-4 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($technicians as $technician)
                                <tr>
                                    <td class="px-5 py-4 font-black text-slate-900">{{ $technician->name }}</td>
                                    <td class="px-5 py-4 text-right font-bold">{{ $technician->jobs }}</td>
                                    <td class="px-5 py-4 text-right font-black">{{ \App\Support\SystemSettings::money((float) $technician->revenue) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-12 text-center text-sm font-bold text-slate-400">No technician report data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
