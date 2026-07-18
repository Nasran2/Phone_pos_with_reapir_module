<x-layouts::app title="Import Shipments">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Import Shipments</h1>
            <p class="text-sm text-slate-500">Manage foreign supplier invoices, shipments and landed costs.</p>
        </div>
        <a href="{{ route('import-shipments.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Shipment
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-100 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date & Ref</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Foreign Total</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Landed Cost (LKR)</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $shipment->reference_no }}</div>
                                <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($shipment->shipment_date)->format('M d, Y') }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-medium text-slate-700">{{ $shipment->supplier->name }}</div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="text-sm font-bold text-slate-800">{{ $shipment->currency->symbol }} {{ number_format($shipment->foreign_total_amount, 2) }}</div>
                                <div class="text-xs text-slate-500">Rate: {{ number_format($shipment->exchange_rate, 4) }}</div>
                            </td>
                            <td class="p-4 text-right text-sm font-bold text-slate-800">
                                Rs. {{ number_format($shipment->total_landed_cost_local, 2) }}
                            </td>
                            <td class="p-4 text-center">
                                @if($shipment->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Finalized & Posted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Draft</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <a href="{{ route('import-shipments.show', $shipment->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                No import shipments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
