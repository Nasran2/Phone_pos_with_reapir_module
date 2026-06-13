<x-layouts::app :title="'Return '.$saleReturn->return_no">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">Return {{ $saleReturn->return_no }}</h2>
            <p class="text-xs text-slate-500">Invoice {{ $saleReturn->sale->invoice_no ?? '-' }}</p>
        </div>
        <a href="{{ route('sales-returns.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 grid md:grid-cols-3 gap-4 text-xs">
                    <div><p class="text-slate-400 font-bold uppercase">Customer</p><p class="font-black text-slate-850">{{ $saleReturn->customer->name ?? '-' }}</p></div>
                    <div><p class="text-slate-400 font-bold uppercase">Return Date</p><p class="font-black text-slate-850">{{ optional($saleReturn->return_date)->format('Y-m-d') }}</p></div>
                    <div><p class="text-slate-400 font-bold uppercase">Refund</p><p class="font-black text-slate-850">{{ $saleReturn->refund_method }} / {{ $saleReturn->refund_status }}</p></div>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr><th class="px-5 py-4">Product</th><th class="px-5 py-4 text-right">Qty</th><th class="px-5 py-4 text-right">Unit Price</th><th class="px-5 py-4 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($saleReturn->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-bold text-slate-850">{{ $item->product->name ?? 'Product' }}</td>
                            <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs h-fit">
                <h3 class="font-black text-slate-850 mb-4">Summary</h3>
                <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Total Return</span><strong>Rs. {{ number_format((float) $saleReturn->total_amount, 2) }}</strong></div>
                <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Refunded</span><strong>Rs. {{ number_format((float) $saleReturn->refund_amount, 2) }}</strong></div>
                <p class="mt-4 text-xs text-slate-500">{{ $saleReturn->reason ?: 'No reason added.' }}</p>
            </div>
        </div>
    </div>
</x-layouts::app>
