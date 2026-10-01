<x-layouts::app :title="'Return '.$saleReturn->return_no">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-black">Return {{ $saleReturn->return_no }}</h2>
            <p class="text-xs text-black">Invoice {{ $saleReturn->sale->invoice_no ?? '-' }}</p>
        </div>
        <a href="{{ route('sales-returns.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 grid md:grid-cols-4 gap-4 text-xs">
                    <div><p class="text-black font-bold uppercase">Customer</p><p class="font-black text-black">{{ $saleReturn->customer->name ?? 'Walk-in' }}</p></div>
                    <div><p class="text-black font-bold uppercase">Return Date</p><p class="font-black text-black">{{ optional($saleReturn->return_date)->format('Y-m-d') }}</p></div>
                    <div>
                        <p class="text-black font-bold uppercase">Return Type</p>
                        <p class="font-black text-black">
                            @if($saleReturn->return_type === 'exchange')
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-[10px]">Exchange</span>
                            @elseif($saleReturn->return_type === 'replacement')
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px]">Replacement</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px]">Refund</span>
                            @endif
                        </p>
                    </div>
                    <div><p class="text-black font-bold uppercase">Refund</p><p class="font-black text-black">{{ $saleReturn->refund_method }} / {{ $saleReturn->refund_status }}</p></div>
                </div>
                
                @if(!$saleReturn->sale_id)
                <div class="px-5 py-3 bg-yellow-50 text-yellow-800 text-xs font-semibold border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    This is a Legacy Return (no original invoice in system).
                </div>
                @endif
                
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-black">
                        <tr><th class="px-5 py-4">Product</th><th class="px-5 py-4 text-right">Qty</th><th class="px-5 py-4 text-right">Unit Value</th><th class="px-5 py-4 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($saleReturn->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-bold text-black">{{ $item->product->name ?? 'Product' }}</td>
                            <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                    <h3 class="font-black text-black mb-4">Summary</h3>
                    <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Return Value</span><strong>Rs. {{ number_format((float) $saleReturn->total_amount, 2) }}</strong></div>
                    <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Refunded</span><strong>Rs. {{ number_format((float) $saleReturn->refund_amount, 2) }}</strong></div>
                    <p class="mt-4 text-xs text-black">{{ $saleReturn->reason ?: 'No reason added.' }}</p>
                </div>
                
                @if($saleReturn->exchange_sale_id)
                <div class="bg-purple-50 rounded-2xl border border-purple-200 p-5 shadow-xs">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xl">🛒</span>
                        <h3 class="font-black text-purple-900">Exchange Invoice</h3>
                    </div>
                    <p class="text-xs text-purple-700 mb-3">An exchange invoice was generated for new products taken by the customer.</p>
                    <a href="{{ route('sales.show', $saleReturn->exchange_sale_id) }}" class="inline-block w-full text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition">View Exchange Invoice &rarr;</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
