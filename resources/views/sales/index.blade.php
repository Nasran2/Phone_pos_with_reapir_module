<x-layouts::app :title="__('Sales Ledger')">
    <div class="contents" x-data="{
        paymentModal: null,
        paymentAmount: 0,
        deductStock: true,
        editingStockRelease: false,
        openPayment(invoice) {
            this.paymentModal = invoice;
            this.paymentAmount = Number(invoice.balance);
            this.deductStock = invoice.inventory_deducted || Number(invoice.balance) > 0;
            this.editingStockRelease = false;
        },
        closePayment() {
            this.paymentModal = null;
            this.paymentAmount = 0;
            this.deductStock = true;
            this.editingStockRelease = false;
        },
        remainingAfterPayment() {
            if (!this.paymentModal) return 0;
            return Math.max(0, Number(this.paymentModal.balance) - Number(this.paymentAmount || 0));
        },
        money(value) {
            return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        editSale(id, status, posType) {
            if (status === 'Paid') {
                Swal.fire({
                    title: 'Edit Paid Bill?',
                    text: 'This bill is already paid. Existing payments will be preserved, but stock deductions will be reversed and re-applied based on your new edits.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, edit it'
                }).then((result) => {
                    if(result.isConfirmed) {
                        window.location.href = @if(\App\Support\Features::wholesaleEnabled())posType === 'wholesale' ? `/pos/wholesale?edit_sale_id=${id}` : @endif`/pos/retail?edit_sale_id=${id}`;
                    }
                });
            } else {
                window.location.href = @if(\App\Support\Features::wholesaleEnabled())posType === 'wholesale' ? `/pos/wholesale?edit_sale_id=${id}` : @endif`/pos/retail?edit_sale_id=${id}`;
            }
        }
    }" @keydown.escape.window="closePayment()">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black">Sales Ledger</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $sales->total() }} Sales</span>
        </div>
        @if(auth()->user()->hasPermission('sales_return.create'))
        <a href="{{ route('sales-returns.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            New Return
        </a>
        @endif
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 {{ \App\Support\Features::wholesaleEnabled() ? 'xl:grid-cols-6' : 'xl:grid-cols-5' }} gap-4">
            @foreach([
                ['Today Sales', $summary['today_sales'], 'blue'],
                ['Retail Sales', $summary['retail_sales'], 'emerald'],
                ...(\App\Support\Features::wholesaleEnabled() ? [['Wholesale Sales', $summary['wholesale_sales'], 'indigo']] : []),
                ['Due Sales', $summary['due_sales'], 'red'],
                ['Total Profit', $summary['total_profit'], 'amber'],
                ['Pending Orders', $summary['pending_orders'], 'orange'],
            ] as [$label, $amount, $color])
            <div class="bg-white border border-slate-200/70 rounded-xl p-4 shadow-xs">
                <p class="text-[11px] font-bold uppercase tracking-wide text-black">{{ $label }}</p>
                <p class="mt-2 text-lg font-black text-black">{{ $label === 'Pending Orders' ? number_format((int) $amount) : 'Rs. '.number_format((float) $amount, 2) }}</p>
            </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-8 gap-3">
                <div class="md:col-span-2 relative">
                    <input name="search" value="{{ request('search') }}" placeholder="Invoice, customer, phone..." class="w-full pl-9 pr-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
                <select name="sale_type" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="retail" @selected(request('sale_type') === 'retail')>Retail</option>
                    @if(\App\Support\Features::wholesaleEnabled())<option value="wholesale" @selected(request('sale_type') === 'wholesale')>Wholesale</option>@endif
                </select>
                <label class="flex items-center gap-2 px-3 py-2.5 border border-slate-200 rounded-lg text-xs font-semibold bg-white cursor-pointer">
                    <input type="checkbox" name="pending_only" value="1" onchange="this.form.submit()" @checked(request()->boolean('pending_only'))>
                    Waiting Payment
                </label>
                <select name="payment_status" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm bg-white">
                    <option value="">All Status</option>
                    @foreach(['Paid', 'Partial', 'Due', 'Cheque Hold', 'Partial + Cheque Hold', 'Partial Paid + Cheque Hold', 'Partial + Cheque Hold + Due', 'Cheque Hold + Due', 'Refunded', 'Cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 border border-slate-900 rounded-lg text-sm">
                <div class="flex gap-2 justify-end">
                    <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black rounded-lg text-xs font-bold">Clear</a>
                    <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-black">
                        <tr>
                            <th class="px-4 py-4 whitespace-nowrap">Invoice No</th>
                            <th class="px-4 py-4 whitespace-nowrap">Date</th>
                            <th class="px-4 py-4">Customer</th>
                            <th class="px-2 py-4 whitespace-nowrap">Type</th>
                            <th class="w-12 px-2 py-4 text-center whitespace-nowrap">Items</th>
                            <th class="px-4 py-4 text-right whitespace-nowrap">Subtotal</th>
                            <th class="w-20 px-2 py-4 text-right whitespace-nowrap">Discount</th>
                            <th class="px-4 py-4 text-right whitespace-nowrap">Grand Total</th>
                            <th class="px-4 py-4 text-right whitespace-nowrap">Paid</th>
                            <th class="px-4 py-4 text-right whitespace-nowrap">Due</th>
                            <th class="px-2 py-4 whitespace-nowrap">Payment</th>
                            <th class="px-2 py-4 whitespace-nowrap">Inventory</th>
                            <th class="px-4 py-4 text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-black">
                        @forelse($sales as $sale)
                        @php
                            $status = $sale->displayStatus();
                            $statusClass = match($status) {
                                'Paid' => 'bg-emerald-100 text-emerald-800',
                                'Partial' => 'bg-amber-100 text-amber-800',
                                'Refunded' => 'bg-blue-100 text-blue-800',
                                'Cancelled' => 'bg-slate-200 text-black',
                                default => 'bg-red-100 text-red-800',
                            };
                            $typeClass = $sale->pos_type === 'wholesale' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800';
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-4 font-bold text-black">{{ $sale->invoice_no }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $sale->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="font-semibold">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                <div class="text-[10px] text-black">{{ $sale->customer->phone ?? '' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-2 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $typeClass }}">{{ ucfirst($sale->pos_type) }}</span></td>
                            <td class="w-12 whitespace-nowrap px-2 py-4 text-center font-semibold">{{ $sale->items_count }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right">Rs. {{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="w-20 whitespace-nowrap px-2 py-4 text-right">Rs. {{ number_format((float) $sale->discount_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-black text-black">Rs. {{ number_format((float) $sale->payable_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-emerald-700 font-bold">Rs. {{ number_format((float) $sale->paid_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-red-600 font-bold">Rs. {{ number_format((float) $sale->due_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-2 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $statusClass }}">{{ $status }}</span></td>
                            <td class="whitespace-nowrap px-2 py-4"><span class="px-2 py-1 rounded-full text-[10px] font-bold {{ $sale->stock_deducted_at ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $sale->inventoryStatus() }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex justify-end gap-1.5">
                                    @if(auth()->user()->hasPermission('sales.edit_payment') && ((float) $sale->due_amount > 0.009 || $sale->stock_deducted_at || $sale->stock_release_on_payment === false) && $sale->status !== 'cancelled')
                                    <button type="button"
                                            @click="openPayment({{ Illuminate\Support\Js::from([
                                                'invoice_no' => $sale->invoice_no,
                                                'customer' => $sale->customer->name ?? 'Walk-in Customer',
                                                'total' => (float) $sale->payable_amount,
                                                'paid' => (float) $sale->paid_amount,
                                                'balance' => (float) $sale->due_amount,
                                                'inventory_deducted' => (bool) $sale->stock_deducted_at,
                                                'payment_url' => route('sales.payments.store', $sale),
                                                'stock_release_url' => route('sales.stock-release.update', $sale),
                                            ]) }})"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-700"
                                            title="Receive payment for {{ $sale->invoice_no }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18v12H3z"/><path d="M3 10h18M7 15h3"/></svg>
                                        <span class="sr-only">Receive Payment</span>
                                    </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.edit') && $sale->status !== 'cancelled')
                                    <button type="button"
                                            @click="editSale({{ $sale->id }}, '{{ $sale->payment_status }}', '{{ $sale->pos_type }}')"
                                            class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50" title="Edit Bill">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.89 1.12l-3.122.921.921-3.122a4.5 4.5 0 011.12-1.89l13.001-13z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125L16.862 4.487" /></svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.invoice_view'))
                                    <a href="{{ route('sales.show', $sale) }}" class="p-1.5 rounded-lg text-black hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.print'))
                                    <a href="{{ route('sales.print', $sale) }}" target="_blank" class="p-1.5 rounded-lg text-black hover:bg-slate-100" title="Print"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.pdf'))
                                    <a href="{{ route('sales.download', $sale) }}" class="p-1.5 rounded-lg text-black hover:bg-slate-100" title="PDF"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales_return.create') && $sale->stock_deducted_at)
                                    <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50" title="Return"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 010 8h-1"/></svg></a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales.delete'))
                                    <form method="POST" action="{{ route('sales.destroy', $sale) }}" onsubmit="return confirmAction(event, 'Delete sale {{ $sale->invoice_no }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="13" class="px-5 py-12 text-center text-black font-semibold">No sales found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $sales->links() }}</div>
        </div>
    </div>

    <div x-show="paymentModal" x-cloak x-transition.opacity class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-5" @click.self="closePayment()">
        <div x-show="paymentModal" x-transition class="max-h-[calc(100dvh-1.5rem)] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-3xl">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">Waiting Payment</p>
                    <h3 class="mt-1 text-lg font-black text-black" x-text="paymentModal && Number(paymentModal.balance) <= 0 ? 'Payment & Stock Release' : 'Receive Customer Payment'">Receive Customer Payment</h3>
                    <p class="mt-1 text-xs text-black"><span x-text="paymentModal ? paymentModal.invoice_no : ''"></span> · <span x-text="paymentModal ? paymentModal.customer : ''"></span></p>
                </div>
                <button type="button" @click="closePayment()" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-2xl text-black hover:bg-slate-100" aria-label="Close payment popup">×</button>
            </div>

            <div class="p-5 sm:p-6">
                <div class="mb-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[9px] font-black uppercase text-black">Total Bill</p>
                        <p class="mt-1 text-sm font-black text-black">Rs. <span x-text="money(paymentModal ? paymentModal.total : 0)"></span></p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <p class="text-[9px] font-black uppercase text-emerald-600">Already Paid</p>
                        <p class="mt-1 text-sm font-black text-emerald-800">Rs. <span x-text="money(paymentModal ? paymentModal.paid : 0)"></span></p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3">
                        <p class="text-[9px] font-black uppercase text-red-500">Balance</p>
                        <p class="mt-1 text-sm font-black text-red-700">Rs. <span x-text="money(paymentModal ? paymentModal.balance : 0)"></span></p>
                    </div>
                </div>

                <form method="POST" :action="paymentModal ? paymentModal.payment_url : '#'"
                      class="space-y-4"
                      >
                    @csrf
                    <input type="hidden" name="deduct_stock_now" :value="deductStock ? 1 : 0">
                    <div x-show="paymentModal && Number(paymentModal.balance) > 0" class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-black">Payment Date</label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="min-h-11 w-full rounded-xl border border-slate-900 px-3 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-black uppercase tracking-wide text-black">Payment Method</label>
                            <select name="payment_method" required class="min-h-11 w-full rounded-xl border border-slate-900 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none">
                                @foreach(['Cash', 'Bank Transfer', 'Card', 'QR Payment'] as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between gap-3">
                            <label class="text-[10px] font-black uppercase tracking-wide text-black">Amount Received</label>
                            <button type="button" @click="paymentAmount = Number(paymentModal.balance)" class="text-[10px] font-black text-blue-600">Use Full Balance</button>
                        </div>
                        <div class="flex min-h-12 items-center rounded-xl border border-slate-200 bg-white px-3 focus-within:border-blue-500">
                            <span class="mr-2 text-sm font-black text-black">Rs.</span>
                            <input name="amount" x-model.number="paymentAmount" type="number" min="0" step="0.01" :max="paymentModal ? paymentModal.balance : 0" required class="w-full border-0 p-0 text-lg font-black text-black outline-none">
                        </div>
                        <div class="mt-2 flex justify-between text-xs font-semibold"><span class="text-black">Balance after payment</span><span class="text-red-600">Rs. <span x-text="money(remainingAfterPayment())"></span></span></div>
                    </div>

                    <input name="reference_no" maxlength="255" placeholder="Reference number (optional)" class="min-h-11 w-full rounded-xl border border-slate-900 px-3 text-sm focus:border-blue-500 focus:outline-none">

                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 space-y-3">
                        <label class="flex items-start gap-3" :class="paymentModal && (paymentModal.inventory_deducted || Number(paymentModal.balance) <= 0) && !editingStockRelease ? 'opacity-60' : ''">
                            <input type="checkbox" x-model="deductStock" :disabled="paymentModal && (paymentModal.inventory_deducted || Number(paymentModal.balance) <= 0) && !editingStockRelease" class="mt-0.5 h-5 w-5 rounded border-amber-400 text-amber-600">
                            <span class="text-xs text-amber-900"><strong class="block text-sm">Release stock with this payment</strong><span x-text="paymentModal && paymentModal.inventory_deducted ? 'Stock has already been released. Edit to return it to allocated stock.' : 'Uncheck to keep the products allocated, even after full payment.'"></span></span>
                        </label>
                        <button type="button" x-show="paymentModal && (paymentModal.inventory_deducted || Number(paymentModal.balance) <= 0) && !editingStockRelease" @click="editingStockRelease = true" class="text-xs font-bold text-blue-600 underline">Edit stock release</button>
                        <div x-show="editingStockRelease" x-cloak class="space-y-2">
                            <p class="text-xs text-amber-900">Unchecking returns all products to allocated stock. Payments stay unchanged.</p>
                            <div class="flex gap-2">
                                <button type="submit" form="ledger-stock-release-form" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white">Save Stock Change</button>
                                <button type="button" @click="editingStockRelease = false; deductStock = !!paymentModal.inventory_deducted" class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-600">Cancel Edit</button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <button type="button" @click="closePayment()" class="min-h-12 rounded-xl border border-slate-200 bg-white text-sm font-bold text-black hover:bg-slate-50">Cancel</button>
                        <button type="submit" x-show="paymentModal && Number(paymentModal.balance) > 0" :disabled="editingStockRelease" :class="editingStockRelease ? 'opacity-50' : ''" class="min-h-12 rounded-xl bg-emerald-600 text-sm font-black text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-700">Save Payment</button>
                    </div>
                </form>
                <form id="ledger-stock-release-form" method="POST" :action="paymentModal ? paymentModal.stock_release_url : '#'">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="stock_release_enabled" :value="deductStock ? 1 : 0">
                </form>
            </div>
        </div>
    </div>
    </div>
</x-layouts::app>
