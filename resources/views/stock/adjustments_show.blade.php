<x-layouts::app :title="__('Adjustment Details')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition" title="Back to Adjustments">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-lg font-bold text-slate-800">Adjustment Details: {{ $stockAdjustment->adjustment_no }}</h2>
        </div>
        <div>
            @if($stockAdjustment->document_path)
            <a 
                href="{{ asset('storage/' . $stockAdjustment->document_path) }}" 
                target="_blank"
                class="flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download Document</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Grid Content -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left Summary Details Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-xs space-y-5 text-xs text-slate-800">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Record Summary</h3>
                
                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Adjustment Code</span>
                    <span class="font-mono font-bold text-slate-800 text-sm">{{ $stockAdjustment->adjustment_no }}</span>
                </div>

                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Adjustment Date</span>
                    <span class="font-bold text-slate-700">{{ $stockAdjustment->adjustment_date ? $stockAdjustment->adjustment_date->format('Y-m-d') : '-' }}</span>
                </div>

                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Adjustment Type</span>
                    <span class="px-2.5 py-0.5 rounded text-[10px] uppercase font-bold inline-block
                        @if($stockAdjustment->adjustment_type === 'increase') bg-emerald-50 text-emerald-700
                        @elseif($stockAdjustment->adjustment_type === 'decrease') bg-rose-50 text-rose-700
                        @elseif($stockAdjustment->adjustment_type === 'opening_stock') bg-blue-50 text-blue-700
                        @else bg-slate-100 text-slate-700 @endif
                    ">
                        {{ str_replace('_', ' ', $stockAdjustment->adjustment_type) }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Created By</span>
                    <span class="font-bold text-slate-700">{{ $stockAdjustment->creator ? $stockAdjustment->creator->name : 'System' }}</span>
                </div>

                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Status</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800">
                        Approved
                    </span>
                </div>

                @if($stockAdjustment->reason)
                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Reason</span>
                    <p class="font-semibold text-slate-750">{{ $stockAdjustment->reason }}</p>
                </div>
                @endif

                @if($stockAdjustment->notes)
                <div>
                    <span class="text-slate-450 block font-semibold mb-0.5">Notes</span>
                    <p class="text-slate-500 italic">{{ $stockAdjustment->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Right Adjusted Items Table -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Adjusted Products List</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 font-bold uppercase text-[9px] tracking-wider">
                                <th class="px-6 py-3">Product Description</th>
                                <th class="px-6 py-3 text-right">Stock Before</th>
                                <th class="px-6 py-3 text-right">Adjustment Qty</th>
                                <th class="px-6 py-3 text-right">Stock After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($stockAdjustment->items as $item)
                            @php
                                $isPositive = $item->quantity > 0;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $item->product->name ?? 'Deleted Product' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $item->product->sku ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-500">
                                    {{ number_format($item->current_stock, 0) }}
                                </td>
                                <td class="px-6 py-4 text-right font-extrabold @if($isPositive) text-emerald-600 @else text-rose-650 @endif">
                                    {{ $isPositive ? '+' : '' }}{{ number_format($item->quantity, 0) }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-800">
                                    {{ number_format($item->new_stock, 0) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-layouts::app>
