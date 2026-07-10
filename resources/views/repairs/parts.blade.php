<x-layouts::app :title="__('Repair Parts Usage')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Parts Usage</h1>
        <p class="mt-1 text-sm text-slate-500">Parts reserved or issued to repair jobs with store and stock reference.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-4">Date</th>
                                <th class="px-5 py-4">Job</th>
                                <th class="px-5 py-4">Part</th>
                                <th class="px-5 py-4">Store</th>
                                <th class="px-5 py-4 text-right">Qty</th>
                                <th class="px-5 py-4 text-right">Line Total</th>
                                <th class="px-5 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($parts as $part)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 text-slate-500">{{ \Carbon\Carbon::parse($part->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-5 py-4"><a class="font-black text-blue-700" href="{{ route('repairs.jobs.show', $part->repair_job_id) }}">{{ $part->job_no }}</a></td>
                                    <td class="px-5 py-4 font-bold text-slate-800">{{ $part->product_name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $part->store_name ?: 'Default' }}</td>
                                    <td class="px-5 py-4 text-right font-bold">{{ number_format((float) $part->quantity, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-black">{{ \App\Support\SystemSettings::money((float) $part->line_total) }}</td>
                                    <td class="px-5 py-4"><span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-700">{{ $part->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-12 text-center text-sm font-bold text-slate-400">No repair parts used yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($parts->hasPages())
                    <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">{{ $parts->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
