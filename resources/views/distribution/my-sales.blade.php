<x-layouts::app :title="__('My Lorry Sales')">
<div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6">
    <div><h2 class="text-lg font-bold text-black">Lorry Sales</h2><p class="text-[10px] font-semibold uppercase tracking-wider text-black">{{ $assignment->lorry_name }} · {{ $assignment->plate_no ?: $assignment->lorry_no }}</p></div>
    <a href="{{ route('distribution.my-lorry.order.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">＋ New Order</a>
</div>
<div class="w-full flex-1 space-y-6 overflow-y-auto p-6 md:p-8">
    <form class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-xs">
        <div class="flex flex-wrap gap-2">@foreach(['today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week','this_month'=>'This Month','last_month'=>'Last Month'] as $key=>$label)<button type="button" data-filter="{{ $key }}" class="quick-sales-filter rounded-lg px-4 py-2 text-xs font-bold {{ request('filter','today')===$key?'bg-blue-600 text-white':'bg-slate-100 text-black hover:bg-slate-200' }}">{{ $label }}</button>@endforeach</div>
        <input type="hidden" name="filter" value="custom">
        <div class="mt-4 grid grid-cols-1 items-end gap-3 md:grid-cols-[150px_150px_1fr_180px_auto]">
            <label class="text-[10px] font-bold uppercase text-black">From<input type="date" name="from" value="{{ request('from',$range[0]) }}" class="mt-1 w-full rounded-lg border-slate-900 text-sm"></label>
            <label class="text-[10px] font-bold uppercase text-black">To<input type="date" name="to" value="{{ request('to',$range[1]) }}" class="mt-1 w-full rounded-lg border-slate-900 text-sm"></label>
            <input name="q" value="{{ request('q') }}" placeholder="Invoice or customer" class="rounded-lg border-slate-900 text-sm">
            <select name="payment_status" class="rounded-lg border-slate-900 text-sm"><option value="">All payments</option>@foreach(['Paid','Partial','Due'] as $status)<option value="{{ $status }}" @selected(request('payment_status')===$status)>{{ $status }}</option>@endforeach</select>
            <button class="rounded-lg bg-slate-800 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-950">Custom Filter</button>
        </div>
    </form>
    <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">@foreach([['Sales',$summary['count']],['Total',\App\Support\SystemSettings::money($summary['total'])],['Paid',\App\Support\SystemSettings::money($summary['paid'])],['Due',\App\Support\SystemSettings::money($summary['due'])]] as $card)<div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-xs"><p class="text-xs font-bold uppercase text-black">{{ $card[0] }}</p><p class="mt-2 text-lg font-extrabold text-black">{{ $card[1] }}</p></div>@endforeach</div>
    <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-xs">
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-[10px] font-bold uppercase text-black"><tr><th class="p-4 text-left">Invoice</th><th class="p-4 text-left">Date</th><th class="p-4 text-left">Customer</th><th class="p-4 text-right">Total</th><th class="p-4 text-right">Paid</th><th class="p-4 text-right">Due</th><th class="p-4 text-center">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($sales as $sale)<tr><td class="p-4 text-xs font-extrabold text-blue-600">{{ $sale->invoice_no }}</td><td class="p-4 text-xs text-black">{{ $sale->sale_date }}</td><td class="p-4 text-xs font-semibold">{{ $sale->customer_name }}</td><td class="p-4 text-right text-xs font-bold">{{ \App\Support\SystemSettings::money($sale->payable_amount) }}</td><td class="p-4 text-right text-xs">{{ \App\Support\SystemSettings::money($sale->paid_amount) }}</td><td class="p-4 text-right text-xs text-red-600">{{ \App\Support\SystemSettings::money($sale->due_amount) }}</td><td class="p-4 text-center"><span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold">{{ $sale->payment_status }}</span></td></tr>@empty<tr><td colspan="7" class="p-16 text-center text-xs text-black">No lorry sales for this period.</td></tr>@endforelse</tbody></table></div>
        <div class="border-t border-slate-100 p-4">{{ $sales->links() }}</div>
    </div>
</div>
<script>document.querySelectorAll('.quick-sales-filter').forEach(button=>button.addEventListener('click',()=>window.location='{{ route('distribution.my-lorry.sales') }}?filter='+button.dataset.filter));</script>
</x-layouts::app>
