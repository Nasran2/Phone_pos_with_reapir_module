<x-layouts::app :title="__('Add Purchase Return')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">Add Purchase Return</h2>
            <p class="text-[11px] text-slate-500 font-semibold">Return quantities are validated against the selected purchase.</p>
        </div>
        <a href="{{ route('purchase-returns.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 bg-slate-50">
        @if($errors->any())
        <div class="mb-5 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-semibold">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('purchase-returns.store') }}" class="bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5 space-y-5">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="text-xs font-bold text-slate-700">Purchase Invoice <span class="text-red-500">*</span></label>
                    <select id="purchase_id" name="purchase_id" required onchange="loadPurchase()" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        <option value="">Please Select</option>
                        @foreach($purchaseData as $purchase)
                        <option value="{{ $purchase['id'] }}" @selected(old('purchase_id') == $purchase['id'])>{{ $purchase['purchase_no'] }} - {{ $purchase['supplier'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700">Return Date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" value="{{ old('return_date', now()->format('Y-m-d')) }}" required class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700">Refund Method</label>
                    <select name="refund_method" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        @foreach(['Supplier Credit', 'Cash', 'Bank Transfer', 'Card', 'Cheque'] as $method)
                        <option value="{{ $method }}" @selected(old('refund_method', 'Supplier Credit') === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700">Refund Status</label>
                    <select name="refund_status" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        @foreach(['Supplier Credit', 'Pending', 'Refunded'] as $status)
                        <option value="{{ $status }}" @selected(old('refund_status', 'Supplier Credit') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700">Refund Amount</label>
                    <input id="refund_amount" name="refund_amount" value="{{ old('refund_amount', 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-xs font-bold text-slate-700">Return Reason <span class="text-red-500">*</span></label>
                    <input name="reason" value="{{ old('reason') }}" required placeholder="Damaged item, wrong product, supplier credit..." class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead class="bg-emerald-600 text-white">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-right">Purchased</th>
                            <th class="px-4 py-3 text-right">Already Returned</th>
                            <th class="px-4 py-3 text-right">Available</th>
                            <th class="px-4 py-3 text-right">Unit Cost</th>
                            <th class="px-4 py-3 text-right">Return Qty</th>
                            <th class="px-4 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody id="return_items_body" class="divide-y divide-slate-100">
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 font-semibold">Select a purchase invoice.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end text-right">
                <div class="bg-slate-50 rounded-xl p-5 min-w-72">
                    <div class="text-xs font-bold text-slate-500 uppercase">Return Amount</div>
                    <div id="return_total" class="text-2xl font-black text-blue-600">Rs. 0.00</div>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold">Save Return</button>
                <a href="{{ route('purchase-returns.index') }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const purchases = @json($purchaseData);

        function money(value) {
            return 'Rs. ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function loadPurchase() {
            const id = Number(document.getElementById('purchase_id').value || 0);
            const purchase = purchases.find(item => Number(item.id) === id);
            const body = document.getElementById('return_items_body');
            if (!purchase) {
                body.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 font-semibold">Select a purchase invoice.</td></tr>';
                calculateReturn();
                return;
            }

            body.innerHTML = purchase.items.map((item, index) => `
                <tr>
                    <td class="px-4 py-3 font-bold text-slate-850">
                        <input type="hidden" name="items[${index}][purchase_item_id]" value="${item.purchase_item_id}">
                        ${item.product_name}
                    </td>
                    <td class="px-4 py-3 text-right">${Number(item.quantity).toFixed(2)}</td>
                    <td class="px-4 py-3 text-right">${Number(item.returned_quantity).toFixed(2)}</td>
                    <td class="px-4 py-3 text-right font-bold">${Number(item.remaining_quantity).toFixed(2)}</td>
                    <td class="px-4 py-3 text-right">${money(item.cost_price)}</td>
                    <td class="px-4 py-3 text-right">
                        <input name="items[${index}][quantity]" type="number" min="0" max="${item.remaining_quantity}" step="0.01" value="0" data-cost="${item.cost_price}" data-max="${item.remaining_quantity}" class="return-qty w-24 px-2 py-2 border border-slate-200 rounded-lg text-right" oninput="calculateReturn()">
                    </td>
                    <td class="px-4 py-3 text-right font-black line-total">Rs. 0.00</td>
                </tr>
            `).join('');
            calculateReturn();
        }

        function calculateReturn() {
            let total = 0;
            document.querySelectorAll('.return-qty').forEach(input => {
                const max = Number(input.dataset.max || 0);
                if (Number(input.value || 0) > max) input.value = max;
                const line = Number(input.value || 0) * Number(input.dataset.cost || 0);
                input.closest('tr').querySelector('.line-total').innerText = money(line);
                total += line;
            });
            document.getElementById('return_total').innerText = money(total);
            document.getElementById('refund_amount').value = total.toFixed(2);
        }

        loadPurchase();
    </script>
</x-layouts::app>
