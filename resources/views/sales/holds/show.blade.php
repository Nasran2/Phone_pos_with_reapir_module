<x-layouts::app :title="'Hold '.$holdSale->reference_no">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">Hold {{ $holdSale->reference_no }}</h2>
            <p class="text-xs text-slate-500">{{ ucfirst($holdSale->pos_type) }} &bull; {{ $holdSale->created_at->format('Y-m-d h:i A') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('hold-sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
            @if(auth()->user()->hasPermission('hold_sales.resume'))
            <form method="POST" action="{{ route('hold-sales.resume', $holdSale) }}">
                @csrf
                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Resume</button>
            </form>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <p class="text-xs text-slate-500">Customer</p>
                    <p class="font-black text-slate-850">{{ $holdSale->customer->name ?? 'Walk-in Customer' }}</p>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr><th class="px-5 py-4">Product</th><th class="px-5 py-4 text-right">Qty</th><th class="px-5 py-4 text-right">Price</th><th class="px-5 py-4 text-right">Subtotal</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($holdSale->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-bold text-slate-850">{{ $item->product->name ?? 'Product' }}</td>
                            <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                            <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->selling_price, 2) }}</td>
                            <td class="px-5 py-4 text-right font-black">Rs. {{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs h-fit">
                <h3 class="font-black text-slate-850 mb-4">Summary</h3>
                <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Items</span><strong>{{ $holdSale->items->count() }}</strong></div>
                <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Discount</span><strong>Rs. {{ number_format((float) $holdSale->discount_amount, 2) }}</strong></div>
                <div class="flex justify-between py-2 border-b border-slate-100 text-xs"><span>Estimated Total</span><strong>Rs. {{ number_format((float) ($holdSale->estimated_total ?: $holdSale->total_amount), 2) }}</strong></div>
                <p class="mt-4 text-xs text-slate-500">{{ $holdSale->note ?: 'No notes added.' }}</p>
            </div>
        </div>
    </div>
</x-layouts::app>
