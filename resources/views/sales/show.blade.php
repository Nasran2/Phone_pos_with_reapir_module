<x-layouts::app :title="'Invoice '.$sale->invoice_no">
    <div class="bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 w-full">
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-slate-900 break-all">Invoice {{ $sale->invoice_no }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $sale->created_at->format('Y-m-d h:i A') }} &bull; {{ \App\Support\Features::wholesaleEnabled() ? ucfirst($sale->pos_type) : 'Sale' }}</p>
        </div>
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black rounded-lg text-xs font-bold">Back</a>
            @if(auth()->user()->hasPermission('sales.print'))
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold">Print</a>
            @endif
            @if(auth()->user()->hasPermission('sales.pdf'))
            <a href="{{ route('sales.download', $sale) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">PDF</a>
            @endif
            @if(auth()->user()->hasPermission('sales.whatsapp_share'))
            @php
                $shareUrl = \Illuminate\Support\Facades\URL::signedRoute('sales.shared', ['sale' => $sale]);
                $message = rawurlencode("Invoice {$sale->invoice_no}\nGrand Total: Rs. ".number_format((float) $sale->payable_amount, 2)."\nPaid: Rs. ".number_format((float) $sale->paid_amount, 2)."\nDue: Rs. ".number_format((float) $sale->due_amount, 2)."\nView invoice: {$shareUrl}");
            @endphp
            <a href="https://wa.me/?text={{ $message }}" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold">WhatsApp</a>
            @endif
            @if((\App\Support\Features::wholesaleEnabled() || $sale->pos_type === 'retail') && auth()->user()->hasPermission('sales_return.create') && $sale->stock_deducted_at)
            <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold">Return Sale</a>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ $errors->first() }}</div>
        @endif

        <section id="invoice-payment" class="rounded-2xl border border-blue-100 bg-white shadow-xs overflow-hidden" aria-label="Invoice payment">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
                <div class="bg-slate-50/70 p-4 sm:p-6 border-b lg:border-b-0 lg:border-r border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="hidden lg:inline text-xs font-bold uppercase tracking-wide text-slate-500">Payment Overview</span>
                        <span class="lg:hidden text-sm font-bold text-slate-900">{{ $sale->customer->name ?? 'Walk-in Customer' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $sale->status === 'cancelled' ? 'bg-red-100 text-red-700' : ($sale->isPaymentComplete() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800') }}">{{ $sale->displayStatus() }}</span>
                    </div>
                    <p class="hidden lg:block mt-4 text-base font-bold text-slate-900">{{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
                    @if($sale->customer?->phone && $sale->customer->phone !== 'N/A')
                    <p class="hidden lg:block mt-1 text-sm text-slate-500">{{ $sale->customer->phone }}</p>
                    @endif
                    <div class="mt-3 lg:mt-5 flex lg:block items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-600">{{ $sale->status === 'cancelled' ? 'Invoice cancelled' : 'Balance Due' }}</p>
                        <p class="lg:mt-1 text-2xl lg:text-4xl font-extrabold tracking-tight {{ (float) $sale->due_amount > 0 ? 'text-red-600' : 'text-emerald-700' }}">Rs. {{ number_format((float) $sale->due_amount, 2) }}</p>
                    </div>
                    <div class="hidden lg:grid mt-5 grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <p class="text-xs font-semibold text-slate-500">Invoice Total</p>
                            <p class="mt-1 text-sm sm:text-base font-bold text-slate-900">Rs. {{ number_format((float) $sale->payable_amount, 2) }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                            <p class="text-xs font-semibold text-emerald-700">Already Paid</p>
                            <p class="mt-1 text-sm sm:text-base font-bold text-emerald-700">Rs. {{ number_format((float) $sale->paid_amount, 2) }}</p>
                        </div>
                    </div>
                    @if((float) $sale->cheque_hold_amount > 0)
                    <p class="mt-3 rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-900"><strong>Awaiting cheque clearance: Rs. {{ number_format((float) $sale->cheque_hold_amount, 2) }}</strong><br>This amount has not cleared yet.</p>
                    @endif
                    @if(!$sale->stock_deducted_at && $sale->status !== 'cancelled')
                    <p class="hidden lg:block mt-4 text-xs leading-relaxed text-slate-500">Products stay allocated until stock is released. The payment form controls when they are released.</p>
                    @elseif($sale->stock_deducted_at)
                    <p class="hidden lg:block mt-4 text-xs text-slate-500">Stock has been deducted for this invoice.</p>
                    @endif
                </div>

                <div class="p-3 sm:p-6">
                    @if((\App\Support\Features::wholesaleEnabled() || $sale->pos_type === 'retail') && auth()->user()->hasPermission('sales.edit_payment') && (float) $sale->due_amount > 0 && $sale->status !== 'cancelled')
                    <form id="receive-payment-form" method="POST" action="{{ route('sales.payments.store', $sale) }}" class="space-y-2 sm:space-y-4">
                        @csrf
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Receive Payment</h3>
                            <p class="mt-1 text-sm text-slate-500">Record the full balance or a partial payment.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:gap-4">
                            <div>
                                <label for="invoice-payment-amount" class="block text-xs font-bold text-slate-600 mb-2">Payment Amount (Rs.)</label>
                                <input id="invoice-payment-amount" name="amount" type="number" step="any" min="0" max="{{ $sale->due_amount }}" value="{{ old('amount', $sale->due_amount) }}" required class="w-full min-w-0 rounded-xl border border-blue-200 bg-blue-50/40 px-3 py-3 text-base font-bold text-blue-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div>
                                <label for="invoice-payment-method" class="block text-xs font-bold text-slate-600 mb-2">Payment Method</label>
                                <select id="invoice-payment-method" name="payment_method" required class="w-full min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    @foreach(['Cash', 'Bank Transfer', 'Card', 'QR Payment'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method', 'Cash') === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="invoice-payment-date" class="block text-xs font-bold text-slate-600 mb-2">Payment Date</label>
                                <input id="invoice-payment-date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required class="w-full min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="invoice-payment-reference" class="block text-xs font-bold text-slate-600 mb-2">Reference <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="invoice-payment-reference" name="reference_no" value="{{ old('reference_no') }}" maxlength="255" placeholder="Bank or transaction reference" class="w-full min-w-0 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>
                        @if(!$sale->stock_deducted_at)
                        <input type="hidden" name="deduct_stock_now" value="0">
                        <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="deduct_stock_now" value="1" @checked(old('deduct_stock_now', true)) class="mt-1 h-4 w-4 shrink-0 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                <span class="text-xs leading-relaxed text-amber-900"><strong class="block text-sm">Release stock with this payment</strong>Uncheck to keep the products allocated, even after full payment.</span>
                            </label>
                        </div>
                        @else
                        <input type="hidden" name="deduct_stock_now" value="1">
                        @endif
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
                            Save Payment
                        </button>
                    </form>
                    @elseif($sale->status === 'cancelled')
                    <div class="py-8"><h3 class="text-lg font-bold text-slate-900">Invoice cancelled</h3><p class="mt-2 text-sm text-slate-500">Payments cannot be recorded for this invoice.</p></div>
                    @elseif($sale->isPaymentComplete())
                    <div class="py-8"><h3 class="text-lg font-bold text-emerald-700">Fully paid</h3><p class="mt-2 text-sm text-slate-500">No further payment is needed. Payment records are shown below.</p></div>
                    @elseif((float) $sale->cheque_hold_amount > 0 && (float) $sale->due_amount <= 0)
                    <div class="py-8"><h3 class="text-lg font-bold text-amber-800">Awaiting cheque clearance</h3><p class="mt-2 text-sm text-slate-500">The cheque is still pending. Check its status in the payment breakdown below.</p></div>
                    @else
                    <div class="py-8"><h3 class="text-lg font-bold text-slate-900">Payment details</h3><p class="mt-2 text-sm text-slate-500">View the balance and payment records for this invoice.</p></div>
                    @endif
                    @if((\App\Support\Features::wholesaleEnabled() || $sale->pos_type === 'retail') && auth()->user()->hasPermission('sales.edit_payment') && $sale->status !== 'cancelled' && ($sale->stock_deducted_at || (float) $sale->due_amount <= 0.009))
                        @include('sales.partials.stock-release-editor')
                    @endif
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 items-start gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-bold text-slate-900">Invoice Details</h3></div>
                <div class="p-5 border-b border-slate-100 grid md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-500">Business</p>
                        <p class="mt-1 font-black text-black">Phone Shop POS</p>
                        <p class="text-xs text-slate-500">{{ \App\Support\Features::wholesaleEnabled() ? 'Retail & Wholesale' : 'Retail & Repair' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-500">Customer</p>
                        <p class="mt-1 font-black text-black">{{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
                        <p class="text-xs text-black">{{ $sale->customer->phone ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-500">Cashier</p>
                        <p class="mt-1 font-black text-black">{{ $sale->cashier->name ?? '-' }}</p>
                        <p class="text-xs text-black">{{ $sale->displayStatus() }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-black">
                            <tr>
                                <th class="px-5 py-4">Product</th>
                                <th class="px-5 py-4 text-right">Qty</th>
                                <th class="px-5 py-4 text-right">Unit Price</th>
                                <th class="px-5 py-4 text-right">Discount</th>
                                <th class="px-5 py-4 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($sale->items as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-black">{{ $item->product->name ?? 'Product' }}</div>
                                    @if($item->variantLabel())
                                    <div class="text-[10px] text-black">{{ $item->variantLabel() }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) ((float) $item->unit_price > 0 ? $item->unit_price : $item->selling_price), 2) }}</td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->discount_amount, 2) }}</td>
                                <td class="px-5 py-4 text-right font-bold">Rs. {{ number_format((float) ((float) $item->line_total > 0 ? $item->line_total : $item->subtotal), 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-black font-semibold">No products recorded for this invoice.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                    <h3 class="font-bold text-slate-900 mb-4">Invoice Totals</h3>
                    @foreach([
                        ['Subtotal', $sale->total_amount],
                        ['Discount', -abs((float) $sale->discount_amount)],
                        ['Tax', $sale->tax_amount],
                        ['Grand Total', $sale->payable_amount],
                        ['Paid Amount', $sale->paid_amount],
                        ['Cheque Hold', $sale->cheque_hold_amount],
                        ['Due Amount', $sale->due_amount],
                    ] as [$label, $amount])
                    <div class="flex justify-between py-2 border-b border-slate-100 text-xs">
                        <span class="font-semibold text-black">{{ $label }}</span>
                        <span class="font-black {{ $label === 'Due Amount' ? 'text-red-600' : 'text-black' }}">Rs. {{ number_format((float) $amount, 2) }}</span>
                    </div>
                    @endforeach
                </div>

            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 items-start gap-6">
            @if($sale->invoicePayments->isNotEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                <h3 class="font-bold text-slate-900 mb-1">Payment Breakdown</h3>
                <p class="mb-4 text-xs text-slate-500">Payment methods and their current status. Cheques are pending until cleared.</p>
                <div class="space-y-2">
                    @foreach($sale->invoicePayments as $payment)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs">
                        <div class="flex items-center justify-between gap-3"><span class="font-bold text-slate-900">{{ $payment->payment_method }}</span><span class="font-black">Rs. {{ number_format((float)$payment->amount, 2) }}</span></div>
                        <div class="mt-1 flex items-center justify-between text-[10px] text-black"><span>{{ $payment->reference_no ?: ($payment->cheque?->cheque_number ? 'Cheque #'.$payment->cheque->cheque_number : 'No reference') }}</span><span class="rounded-full px-2 py-0.5 font-bold uppercase {{ $payment->status === 'cleared' ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 'hold' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ $payment->status }}</span></div>
                        @if($payment->cheque)<p class="mt-1 text-[10px] text-black">{{ $payment->cheque->bank_name }} · {{ optional($payment->cheque->cheque_date)->format('Y-m-d') }} · {{ $payment->cheque->current_status }}</p>@endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                <h3 class="font-bold text-slate-900 mb-4">Payment History</h3>
                <div class="space-y-2">
                    @forelse($sale->payments as $payment)
                    <div class="flex justify-between text-xs bg-slate-50 rounded-lg p-3">
                        <div>
                            <p class="font-bold text-black">{{ $payment->payment_method }}</p>
                            <p class="text-black">{{ optional($payment->payment_date)->format('Y-m-d') }} {{ $payment->reference_no }}</p>
                        </div>
                        <p class="font-black text-emerald-700">Rs. {{ number_format((float) $payment->amount, 2) }}</p>
                    </div>
                    @empty
                    <p class="text-xs text-black font-semibold">No payments received yet.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-layouts::app>
