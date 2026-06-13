<x-layouts::app :title="__('Create Sale Return')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <h2 class="text-lg font-bold text-slate-850">Create Sale Return</h2>
        <a href="{{ route('sales-returns.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('sales-returns.create') }}" class="grid md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="text-[11px] font-bold uppercase text-slate-500">Search Invoice / Customer</label>
                    <input name="invoice_search" value="{{ request('invoice_search') }}" placeholder="INV no, customer, phone..." class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="md:col-span-3">
                    <label class="text-[11px] font-bold uppercase text-slate-500">Select Matching Invoice</label>
                    <select name="sale_id" class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white">
                        <option value="">Please Select</option>
                        @foreach($sales as $sale)
                        <option value="{{ $sale->id }}" @selected(optional($selectedSale)->id === $sale->id)>{{ $sale->invoice_no }} - {{ $sale->customer->name ?? 'Walk-in Customer' }} {{ $sale->customer?->phone ? '(' . $sale->customer->phone . ')' : '' }} - Rs. {{ number_format((float) $sale->payable_amount, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('sales-returns.create') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Clear</a>
                    <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Search / Load</button>
                </div>
            </form>
        </div>

        @if($selectedSale)
        <form method="POST" action="{{ route('sales-returns.store') }}" class="grid lg:grid-cols-3 gap-6">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $selectedSale->id }}">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-slate-850">{{ $selectedSale->invoice_no }}</h3>
                        <p class="text-xs text-slate-500">{{ $selectedSale->customer->name ?? 'Walk-in Customer' }} &bull; {{ ucfirst($selectedSale->pos_type) }}</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">Grand Total Rs. {{ number_format((float) $selectedSale->payable_amount, 2) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-4">Product</th>
                                <th class="px-5 py-4 text-right">Sold</th>
                                <th class="px-5 py-4 text-right">Returned</th>
                                <th class="px-5 py-4 text-right">Return Qty</th>
                                <th class="px-5 py-4 text-right">Unit Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($selectedSale->items as $index => $item)
                            @php
                                $returnedQty = (float) $item->returnedItems->sum('quantity');
                                $remainingQty = max(0, (float) $item->quantity - $returnedQty);
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                    <div class="font-bold text-slate-850">{{ $item->product->name ?? 'Product' }}</div>
                                    @if($item->variantLabel())<div class="text-[10px] text-slate-500">{{ $item->variantLabel() }}</div>@endif
                                </td>
                                <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                                <td class="px-5 py-4 text-right">{{ $returnedQty }}</td>
                                <td class="px-5 py-4 text-right">
                                    <input type="number" step="0.01" min="0" max="{{ $remainingQty }}" name="items[{{ $index }}][quantity]" value="0" class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-xs text-right">
                                </td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) ($item->unit_price ?: $item->selling_price), 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs space-y-4">
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Return Date</label>
                    <input type="date" name="return_date" value="{{ now()->toDateString() }}" required class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Refund Method</label>
                    <select name="refund_method" class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white">
                        @foreach(['Cash', 'Bank Transfer', 'Card', 'Customer Credit', 'No Refund'] as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Refund Amount</label>
                    <input type="number" step="0.01" min="0" name="refund_amount" value="0" class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Reason</label>
                    <textarea name="reason" rows="4" class="mt-1 w-full px-3 py-2 border border-slate-200 rounded-lg text-xs" placeholder="Damaged item, customer changed item..."></textarea>
                </div>
                <button class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Save Return</button>
            </div>
        </form>
        @endif
    </div>
</x-layouts::app>
