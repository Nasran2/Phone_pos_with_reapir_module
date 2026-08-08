<x-layouts::app :title="'Invoice '.$sale->invoice_no">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">Invoice {{ $sale->invoice_no }}</h2>
            <p class="text-xs text-slate-500">{{ $sale->created_at->format('Y-m-d h:i A') }} &bull; {{ ucfirst($sale->pos_type) }}</p>
        </div>
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
            @if(auth()->user()->hasPermission('sales.print'))
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Print</a>
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
            @if(auth()->user()->hasPermission('sales_return.create') && $sale->stock_deducted_at)
            <a href="{{ route('sales-returns.create', ['sale_id' => $sale->id]) }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold">Return Sale</a>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ $errors->first() }}</div>
        @endif

        @if(!$sale->stock_deducted_at && $sale->status !== 'cancelled')
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-900 text-xs font-semibold">
            Pending order: inventory and serial/IMEI stock remain unchanged until the outstanding payment has fully cleared.
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 grid md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-400">Business</p>
                        <p class="mt-1 font-black text-slate-850">Phone Shop POS</p>
                        <p class="text-xs text-slate-500">Retail & Wholesale</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-400">Customer</p>
                        <p class="mt-1 font-black text-slate-850">{{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
                        <p class="text-xs text-slate-500">{{ $sale->customer->phone ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase text-slate-400">Cashier</p>
                        <p class="mt-1 font-black text-slate-850">{{ $sale->cashier->name ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $sale->displayStatus() }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
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
                                    <div class="font-bold text-slate-850">{{ $item->product->name ?? 'Product' }}</div>
                                    @if($item->variantLabel())
                                    <div class="text-[10px] text-slate-500">{{ $item->variantLabel() }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">{{ (float) $item->quantity }}</td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) ((float) $item->unit_price > 0 ? $item->unit_price : $item->selling_price), 2) }}</td>
                                <td class="px-5 py-4 text-right">Rs. {{ number_format((float) $item->discount_amount, 2) }}</td>
                                <td class="px-5 py-4 text-right font-bold">Rs. {{ number_format((float) ((float) $item->line_total > 0 ? $item->line_total : $item->subtotal), 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-400 font-semibold">No products recorded for this invoice.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                    <h3 class="font-black text-slate-850 mb-4">Totals</h3>
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
                        <span class="font-semibold text-slate-500">{{ $label }}</span>
                        <span class="font-black {{ $label === 'Due Amount' ? 'text-red-600' : 'text-slate-850' }}">Rs. {{ number_format((float) $amount, 2) }}</span>
                    </div>
                    @endforeach
                </div>

                @if($sale->invoicePayments->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                    <h3 class="font-black text-slate-850 mb-1">Payment Allocation</h3>
                    <p class="mb-4 text-[10px] font-semibold text-slate-400">Each row is tracked independently. Cheques remain on hold until passed.</p>
                    <div class="space-y-2">
                        @foreach($sale->invoicePayments as $payment)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs">
                            <div class="flex items-center justify-between gap-3"><span class="font-black text-slate-800">{{ $payment->payment_method }}</span><span class="font-black">Rs. {{ number_format((float)$payment->amount, 2) }}</span></div>
                            <div class="mt-1 flex items-center justify-between text-[10px] text-slate-500"><span>{{ $payment->reference_no ?: ($payment->cheque?->cheque_number ? 'Cheque #'.$payment->cheque->cheque_number : 'No reference') }}</span><span class="rounded-full px-2 py-0.5 font-bold uppercase {{ $payment->status === 'cleared' ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 'hold' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ $payment->status }}</span></div>
                            @if($payment->cheque)<p class="mt-1 text-[10px] text-slate-500">{{ $payment->cheque->bank_name }} · {{ optional($payment->cheque->cheque_date)->format('Y-m-d') }} · {{ $payment->cheque->current_status }}</p>@endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs">
                    <h3 class="font-black text-slate-850 mb-4">Payments</h3>
                    <div class="space-y-2">
                        @forelse($sale->payments as $payment)
                        <div class="flex justify-between text-xs bg-slate-50 rounded-lg p-3">
                            <div>
                                <p class="font-bold text-slate-800">{{ $payment->payment_method }}</p>
                                <p class="text-slate-400">{{ optional($payment->payment_date)->format('Y-m-d') }} {{ $payment->reference_no }}</p>
                            </div>
                            <p class="font-black text-emerald-700">Rs. {{ number_format((float) $payment->amount, 2) }}</p>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400 font-semibold">No payment records.</p>
                        @endforelse
                    </div>
                </div>

                @if(auth()->user()->hasPermission('sales.edit_payment') && (float) $sale->due_amount > 0)
                <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-xs space-y-3">
                    @csrf
                    <h3 class="font-black text-slate-850">Record Due Payment</h3>
                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <select name="payment_method" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        @foreach(['Cash', 'Bank Transfer', 'Card', 'QR Payment'] as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                        @endforeach
                    </select>
                    <input name="amount" type="number" step="any" max="{{ $sale->due_amount }}" value="{{ $sale->due_amount }}" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="reference_no" placeholder="Reference no" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                    @if(!$sale->stock_deducted_at)
                    <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[10px] font-semibold text-amber-900">
                        <input type="checkbox" name="deduct_stock_now" value="1" class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                        <span><strong class="block text-xs">Deduct stock with this payment</strong>Use this only when releasing goods before the invoice is fully paid. The remaining balance will stay due.</span>
                    </label>
                    @endif
                    <button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Save Payment</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
