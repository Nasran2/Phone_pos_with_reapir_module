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
            <form method="GET" action="{{ route('sales-returns.create') }}" id="invoice-search-form" class="grid md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-5 relative" id="invoice-select-container">
                    <label class="text-[11px] font-bold uppercase text-slate-500">Search & Select Invoice / Customer</label>
                    <div class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white flex justify-between items-center cursor-pointer focus-within:ring-1 focus-within:ring-blue-500" onclick="document.getElementById('invoice-dropdown').classList.toggle('hidden'); document.getElementById('invoice-search').focus();">
                        <span id="invoice-select-text" class="text-slate-800 truncate block">
                            @if($selectedSale)
                                {{ $selectedSale->invoice_no }} - {{ $selectedSale->customer->name ?? 'Walk-in Customer' }} - Rs. {{ number_format((float) $selectedSale->payable_amount, 2) }}
                            @else
                                {{ request('invoice_search') ? 'Showing results for "'.request('invoice_search').'"...' : 'Please Select...' }}
                            @endif
                        </span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    
                    <div id="invoice-dropdown" class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl hidden">
                        <div class="p-2 border-b border-slate-100 sticky top-0 bg-white rounded-t-lg">
                            <input type="text" name="invoice_search" value="{{ request('invoice_search') }}" id="invoice-search" placeholder="Type customer name, invoice no, phone and press Enter to search server..." class="w-full px-3 py-2 border border-slate-200 rounded-md text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onkeyup="filterInvoices()">
                        </div>
                        <div class="max-h-60 overflow-y-auto p-1" id="invoice-list">
                            @foreach($sales as $sale)
                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 cursor-pointer rounded invoice-item border-b border-slate-50 last:border-0" onclick="selectInvoice('{{ $sale->id }}', '{{ $sale->invoice_no }} - {{ addslashes($sale->customer->name ?? 'Walk-in Customer') }}')">
                                    <input type="radio" name="sale_id" value="{{ $sale->id }}" class="hidden">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-800 invoice-search-text">{{ $sale->invoice_no }} - {{ $sale->customer->name ?? 'Walk-in Customer' }} {{ $sale->customer?->phone ? '(' . $sale->customer->phone . ')' : '' }}</span>
                                        <span class="text-xs text-slate-500">Amount: Rs. {{ number_format((float) $sale->payable_amount, 2) }} &bull; Date: {{ $sale->created_at->format('Y-m-d') }}</span>
                                    </div>
                                </label>
                            @endforeach
                            <div id="no-invoice-results" class="hidden p-4 text-center text-sm text-slate-500">No matching invoices found.</div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-1 flex gap-2 justify-end">
                    <a href="{{ route('sales-returns.create') }}" class="w-full flex items-center justify-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold">Clear</a>
                    <button class="hidden">Search</button>
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
                                    <input type="number" step="any" min="0" max="{{ $remainingQty }}" name="items[{{ $index }}][quantity]" value="0" class="w-24 px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-right">
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
                    <select name="refund_method" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        @foreach(['Cash', 'Bank Transfer', 'Card', 'Customer Credit', 'No Refund'] as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Refund Amount</label>
                    <input type="number" step="any" min="0" name="refund_amount" value="0" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Reason</label>
                    <textarea name="reason" rows="4" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm" placeholder="Damaged item, customer changed item..."></textarea>
                </div>
                <button class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Save Return</button>
            </div>
        </form>
        @endif
    </div>
</x-layouts::app>

<script>
function filterInvoices() {
    let input = document.getElementById('invoice-search').value.toLowerCase();
    let items = document.querySelectorAll('.invoice-item');
    let visibleCount = 0;
    
    items.forEach(item => {
        let text = item.querySelector('.invoice-search-text').innerText.toLowerCase();
        if (text.includes(input)) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    document.getElementById('no-invoice-results').style.display = visibleCount === 0 ? 'block' : 'none';
}

function selectInvoice(id, text) {
    const radio = document.querySelector('input[name="sale_id"][value="' + id + '"]');
    if (radio) radio.checked = true;
    
    document.getElementById('invoice-select-text').innerText = text;
    document.getElementById('invoice-dropdown').classList.add('hidden');
    
    document.getElementById('invoice-search-form').submit();
}

document.addEventListener('click', function(event) {
    const ms = document.getElementById('invoice-select-container');
    if (ms && !ms.contains(event.target)) {
        const dropdown = document.getElementById('invoice-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }
});
</script>
