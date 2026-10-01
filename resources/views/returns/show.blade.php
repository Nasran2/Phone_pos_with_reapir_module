<x-layouts::app :title="__('Purchase Return Details')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-black">{{ $return->return_no }}</h2>
            <p class="text-[11px] text-black font-semibold">Purchase return details</p>
        </div>
        <a href="{{ route('purchase-returns.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-black rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 bg-slate-50 space-y-6">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div><div class="text-xs font-bold text-black uppercase">Purchase No</div><div class="font-bold">{{ $return->purchase?->displayNo() ?? '-' }}</div></div>
                <div><div class="text-xs font-bold text-black uppercase">Supplier</div><div class="font-bold">{{ $return->supplier->name ?? '-' }}</div></div>
                <div><div class="text-xs font-bold text-black uppercase">Return Date</div><div class="font-bold">{{ optional($return->return_date)->format('Y-m-d') }}</div></div>
                <div><div class="text-xs font-bold text-black uppercase">Refund Method</div><div class="font-bold">{{ $return->refund_method }}</div></div>
                <div><div class="text-xs font-bold text-black uppercase">Refund Status</div><div class="font-bold">{{ $return->refund_status }}</div></div>
                <div><div class="text-xs font-bold text-black uppercase">Refund Amount</div><div class="font-bold">Rs. {{ number_format((float) $return->refund_amount, 2) }}</div></div>
                <div class="md:col-span-3"><div class="text-xs font-bold text-black uppercase">Reason</div><div class="font-semibold">{{ $return->reason }}</div></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5">
                <div class="text-xs font-bold text-black uppercase">Return Amount</div>
                <div class="text-3xl font-black text-blue-600 mt-2">Rs. {{ number_format((float) $return->total_amount, 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 font-bold text-black">Returned Items</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-black uppercase">
                        <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3 text-right">Qty</th><th class="px-5 py-3 text-right">Cost</th><th class="px-5 py-3 text-right">Subtotal</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($return->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-bold">{{ $item->product->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->cost_price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>
