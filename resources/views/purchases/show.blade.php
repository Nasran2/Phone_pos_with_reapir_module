<x-layouts::app :title="__('Purchase Details')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">{{ $purchase->displayNo() }}</h2>
            <p class="text-[11px] text-slate-500 font-semibold">Purchase invoice details</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchases.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
            @if(auth()->user()->hasPermission('purchase.edit'))
            <a href="{{ route('purchases.edit', $purchase) }}" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Edit</a>
            @endif
            @if(auth()->user()->hasPermission('purchase.print'))
            <a href="{{ route('purchases.print', $purchase) }}" target="_blank" class="px-3 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Print</a>
            @endif
            @if(auth()->user()->hasPermission('purchase.download'))
            <a href="{{ route('purchases.download', $purchase) }}" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold">PDF</a>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 bg-slate-50 space-y-6">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Supplier</div><div class="font-bold text-slate-850">{{ $purchase->supplier->name ?? '-' }}</div><div class="text-xs text-slate-500">{{ $purchase->supplier->address ?? '' }}</div></div>
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Purchase Date</div><div class="font-bold">{{ optional($purchase->purchase_date)->format('Y-m-d') }}</div></div>
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Reference</div><div class="font-bold">{{ $purchase->reference_no ?: '-' }}</div></div>
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Status</div><div class="font-bold">{{ $purchase->status }}</div></div>
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Stock Updated</div><div class="font-bold">{{ $purchase->stock_updated ? 'Yes' : 'No' }}</div></div>
                    <div><div class="text-xs font-bold text-slate-400 uppercase">Document</div>
                        @if($purchase->document_path)
                        <a href="{{ asset('storage/' . $purchase->document_path) }}" target="_blank" class="font-bold text-blue-600">View Attachment</a>
                        @else
                        <span class="font-bold">-</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5 space-y-3 text-sm">
                <div class="flex justify-between"><span class="font-bold text-slate-600">Subtotal</span><span>Rs. {{ number_format((float) $purchase->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="font-bold text-slate-600">Discount</span><span>(-) Rs. {{ number_format((float) $purchase->discount_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="font-bold text-slate-600">Tax</span><span>(+) Rs. {{ number_format((float) $purchase->tax_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="font-bold text-slate-600">Shipping</span><span>(+) Rs. {{ number_format((float) $purchase->shipping_cost, 2) }}</span></div>
                <div class="pt-3 border-t flex justify-between"><span class="font-black">Grand Total</span><span class="font-black text-blue-600">Rs. {{ number_format((float) $purchase->grand_total, 2) }}</span></div>
                <div class="flex justify-between"><span class="font-bold text-slate-600">Paid</span><span class="font-bold text-emerald-600">Rs. {{ number_format((float) $purchase->paid_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="font-bold text-slate-600">Due</span><span class="font-bold text-red-600">Rs. {{ number_format((float) $purchase->due_amount, 2) }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-850">Purchase Items</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase">
                        <tr>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3 text-right">Qty</th>
                            <th class="px-5 py-3 text-right">Unit Cost</th>
                            <th class="px-5 py-3 text-right">Discount</th>
                            <th class="px-5 py-3 text-right">Tax</th>
                            <th class="px-5 py-3 text-right">Retail</th>
                            <th class="px-5 py-3 text-right">Wholesale</th>
                            <th class="px-5 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($purchase->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-bold">{{ $item->product->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) ($item->unit_cost ?: $item->cost_price), 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->discount_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->tax_amount, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->retail_price, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->wholesale_price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-slate-400 font-semibold">No products recorded for this purchase.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchase->notes)
        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5">
            <div class="text-xs font-bold text-slate-400 uppercase mb-2">Notes</div>
            <p class="text-sm text-slate-700 whitespace-pre-line">{{ $purchase->notes }}</p>
        </div>
        @endif
    </div>
</x-layouts::app>
