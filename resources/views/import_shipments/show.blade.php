<x-layouts::app title="Import Shipment #{{ $importShipment->reference_no }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Shipment #{{ $importShipment->reference_no }}</h1>
            <p class="text-sm text-black">
                Supplier: {{ $importShipment->supplier->name }} | 
                Date: {{ \Carbon\Carbon::parse($importShipment->shipment_date)->format('M d, Y') }} |
                Store: {{ $importShipment->store->name }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('import-shipments.index') }}" class="text-black hover:text-black font-medium text-sm transition-colors py-2">
                &larr; Back
            </a>
            @if($importShipment->status === 'draft')
                <button type="button" onclick="document.getElementById('finalizeModal').style.display='flex'" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Finalize Shipment & Landed Costs
                </button>
            @endif
        </div>
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
    @if($errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="text-sm font-medium text-black mb-1">Foreign Total</div>
            <div class="text-2xl font-bold text-black">{{ $importShipment->currency->symbol }} {{ number_format($importShipment->foreign_total_amount, 2) }}</div>
            <div class="text-xs text-black mt-1">@ Exchange Rate: {{ number_format($importShipment->exchange_rate, 4) }}</div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="text-sm font-medium text-black mb-1">Local Total (Goods Value)</div>
            <div class="text-2xl font-bold text-black">Rs. {{ number_format($importShipment->local_total_amount, 2) }}</div>
        </div>

        <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-200 p-6">
            <div class="text-sm font-medium text-blue-700 mb-1">Total Landed Cost (Local)</div>
            <div class="text-2xl font-bold text-blue-900">Rs. {{ number_format($importShipment->total_landed_cost_local, 2) }}</div>
            @if($importShipment->status === 'completed')
                <div class="text-xs text-blue-600 mt-1 font-medium">Includes Freight, Insurance, Clearing & Levies</div>
            @else
                <div class="text-xs text-blue-600 mt-1">Not finalized yet</div>
            @endif
        </div>
    </div>

    @if($importShipment->status === 'completed')
    <!-- Cost Breakdown -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h3 class="text-lg font-bold text-black mb-4">Landed Costs Breakdown</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-center">
                <div class="text-xs text-black uppercase font-bold mb-1">Freight</div>
                <div class="text-sm font-bold text-black">Rs. {{ number_format($importShipment->total_freight_local, 2) }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-center">
                <div class="text-xs text-black uppercase font-bold mb-1">Insurance</div>
                <div class="text-sm font-bold text-black">Rs. {{ number_format($importShipment->total_insurance_local, 2) }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-center">
                <div class="text-xs text-black uppercase font-bold mb-1">Clearing</div>
                <div class="text-sm font-bold text-black">Rs. {{ number_format($importShipment->total_clearing_local, 2) }}</div>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-center">
                <div class="text-xs text-black uppercase font-bold mb-1">Other</div>
                <div class="text-sm font-bold text-black">Rs. {{ number_format($importShipment->total_other_costs_local, 2) }}</div>
            </div>
            <div class="p-3 bg-rose-50 rounded-lg border border-rose-100 text-center">
                <div class="text-xs text-rose-600 uppercase font-bold mb-1">Customs Levies</div>
                <div class="text-sm font-bold text-rose-800">Rs. {{ number_format($importShipment->total_customs_levies_local, 2) }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-black">Items List</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200">
                        <th class="p-3 text-xs font-bold text-black uppercase">Product</th>
                        <th class="p-3 text-xs font-bold text-black uppercase">HS Code</th>
                        <th class="p-3 text-xs font-bold text-black uppercase text-right">Qty</th>
                        <th class="p-3 text-xs font-bold text-black uppercase text-right">Foreign Price</th>
                        <th class="p-3 text-xs font-bold text-black uppercase text-right">Local Total</th>
                        @if($importShipment->status === 'completed')
                        <th class="p-3 text-xs font-bold text-black uppercase text-right">Customs Levies</th>
                        <th class="p-3 text-xs font-bold text-black uppercase text-right">Allocated Costs</th>
                        <th class="p-3 text-xs font-bold text-blue-600 uppercase text-right">Unit Landed Cost (LKR)</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($importShipment->items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3">
                                <div class="font-medium text-black text-sm">{{ $item->product->name }}</div>
                            </td>
                            <td class="p-3 text-sm text-black">
                                {{ $item->hsCode ? $item->hsCode->code : 'None' }}
                            </td>
                            <td class="p-3 text-sm font-bold text-black text-right">{{ floatval($item->quantity) }}</td>
                            <td class="p-3 text-sm text-black text-right">{{ $importShipment->currency->symbol }} {{ number_format($item->foreign_unit_price, 2) }}</td>
                            <td class="p-3 text-sm text-black text-right">Rs. {{ number_format($item->local_line_total, 2) }}</td>
                            
                            @if($importShipment->status === 'completed')
                                @php
                                    $itemLevies = $item->customs_duty_amount + $item->pal_amount + $item->cess_amount + $item->srid_amount + $item->sscl_amount;
                                    $itemAllocations = $item->allocated_freight + $item->allocated_insurance + $item->allocated_clearing + $item->allocated_other;
                                @endphp
                                <td class="p-3 text-sm text-rose-600 text-right">+ Rs. {{ number_format($itemLevies, 2) }}</td>
                                <td class="p-3 text-sm text-amber-600 text-right">+ Rs. {{ number_format($itemAllocations, 2) }}</td>
                                <td class="p-3 text-sm font-bold text-blue-700 text-right">Rs. {{ number_format($item->landed_unit_cost_local, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($importShipment->status === 'draft')
    <!-- Finalize Modal -->
    <div id="finalizeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-xl overflow-hidden" @click.stop>
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-black">Finalize Shipment & Calculate Landed Costs</h3>
                <button type="button" onclick="document.getElementById('finalizeModal').style.display='none'" class="text-black hover:text-black transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form method="POST" action="{{ route('import-shipments.finalize', $importShipment->id) }}">
                @csrf
                <div class="p-6 space-y-4">
                    <p class="text-sm text-black">Enter the local (LKR) amounts for the following landed costs. These will be automatically apportioned to items based on their relative value, and all HS Code duties/levies will be applied.</p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">Total Freight (LKR)</label>
                            <input type="number" step="0.01" min="0" name="freight_local" value="0.00" class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">Total Insurance (LKR)</label>
                            <input type="number" step="0.01" min="0" name="insurance_local" value="0.00" class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">Total Clearing Charges (LKR)</label>
                            <input type="number" step="0.01" min="0" name="clearing_local" value="0.00" class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">Other Costs (LKR)</label>
                            <input type="number" step="0.01" min="0" name="other_local" value="0.00" class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <p class="text-sm font-medium text-amber-800">
                            <strong>Warning:</strong> Finalizing this shipment will calculate all landed costs, update the Product Cost Price (MAC), and post stock into inventory permanently. This action cannot be reversed.
                        </p>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('finalizeModal').style.display='none'" class="px-4 py-2 text-sm font-medium text-black hover:text-black transition-colors">Cancel</button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors shadow-sm">Confirm & Finalize</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</x-layouts::app>
