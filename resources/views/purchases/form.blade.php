@php
    $isEdit = filled($purchase);
    $title = $isEdit ? 'Edit Purchase' : 'Add Purchase';
    $payment = $isEdit ? $purchase->payments->first() : null;
    $initialItems = collect(old('items', $isEdit ? $purchase->items->map(fn($item) => [
        'product_id' => $item->product_id,
        'quantity' => (float) $item->quantity,
        'unit_cost' => (float) ($item->unit_cost ?: $item->cost_price),
        'discount_percent' => (float) $item->discount_percent,
        'discount_amount' => (float) $item->discount_amount,
        'tax_percent' => (float) $item->tax_percent,
        'profit_margin' => (float) $item->profit_margin,
        'retail_price' => (float) $item->retail_price,
        'wholesale_price' => (float) $item->wholesale_price,
    ])->values()->all() : []))->values();
@endphp

<x-layouts::app :title="$title">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div>
            <h2 class="text-lg font-bold text-slate-850">{{ $title }}</h2>
            <p class="text-[11px] text-slate-500 font-semibold">Purchases are stock-safe only after status becomes Received.</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Back</a>
    </div>

    <div class="p-6 md:p-8 bg-slate-50 flex-1">
        @if($errors->any())
        <div class="mb-5 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-semibold">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @if(session('success'))
        <div class="mb-5 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('purchases.update', $purchase) : route('purchases.store') }}" class="space-y-6">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs p-5 space-y-5">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-bold text-slate-700">Supplier <span class="text-red-500">*</span></label>
                        <div class="mt-1 flex gap-2">
                            <select id="supplier_id" name="supplier_id" required class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-blue-500" onchange="fillSupplierAddress()">
                                <option value="">Please Select</option>
                                @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @if(auth()->user()->hasPermission('suppliers.create'))
                            <button type="button" onclick="openSupplierModal()" class="px-3 rounded-lg bg-blue-600 text-white font-bold text-lg">+</button>
                            @endif
                        </div>
                    </div>
                    @if($enableMultiStore)
                    <div>
                        <label class="text-xs font-bold text-slate-700">Store <span class="text-red-500">*</span></label>
                        <select name="store_id" required class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-blue-500">
                            @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('store_id', $purchase->store_id ?? \App\Models\Setting::get('default_store_id', 1)) == $store->id)>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="text-xs font-bold text-slate-700">Reference No</label>
                        <input name="reference_no" value="{{ old('reference_no', $purchase->reference_no ?? '') }}" placeholder="Optional" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700">Purchase Date <span class="text-red-500">*</span></label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date', $isEdit ? optional($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" required class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700">Purchase Status <span class="text-red-500">*</span></label>
                        <select name="status" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-1 focus:ring-blue-500">
                            @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $purchase->status ?? 'Received') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-bold text-slate-700">Address</label>
                        <input id="supplier_address" readonly placeholder="Auto-filled" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-bold text-slate-700">Attach Document</label>
                        <input type="file" name="document" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                        <p class="mt-1 text-[11px] text-slate-500">Max 5MB. Allowed: pdf, csv, zip, doc, docx, jpeg, jpg, png</p>
                        @if($isEdit && $purchase->document_path)
                        <a href="{{ asset('storage/' . $purchase->document_path) }}" target="_blank" class="text-[11px] font-bold text-blue-600">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="relative">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <input id="product_search" type="text" placeholder="Enter product name / SKU / Scan barcode" class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500" oninput="searchProducts(this.value)" onkeydown="handleProductSearchKey(event)">
                            <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <div id="product_results" class="hidden absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden max-h-72 overflow-y-auto"></div>
                        </div>
                        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            Add new product
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full min-w-[1150px] text-left">
                        <thead class="bg-emerald-600 text-white text-xs">
                            <tr>
                                <th class="px-3 py-3 w-10">#</th>
                                <th class="px-3 py-3">Product Name</th>
                                <th class="px-3 py-3 text-right">Quantity</th>
                                <th class="px-3 py-3 text-right">Unit Cost</th>
                                <th class="px-3 py-3 text-right">Discount %</th>
                                <th class="px-3 py-3 text-right">Discount Amount</th>
                                <th class="px-3 py-3 text-right">Tax %</th>
                                <th class="px-3 py-3 text-right">Line Total</th>
                                <th class="px-3 py-3 text-right">Profit %</th>
                                <th class="px-3 py-3 text-right">Retail Price</th>
                                <th class="px-3 py-3 text-right">Wholesale Price</th>
                                <th class="px-3 py-3 text-center">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="items_body" class="divide-y divide-slate-100 text-xs"></tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-700">Discount Type</label>
                                <select id="discount_type" name="discount_type" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white" onchange="recalculate()">
                                    <option value="none" @selected(old('discount_type', $purchase->discount_type ?? 'none') === 'none')>None</option>
                                    <option value="fixed" @selected(old('discount_type', $purchase->discount_type ?? '') === 'fixed')>Fixed amount</option>
                                    <option value="percentage" @selected(old('discount_type', $purchase->discount_type ?? '') === 'percentage')>Percentage</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700">Discount Amount</label>
                                <input id="discount_amount" name="discount_amount" value="{{ old('discount_amount', $purchase->discount_amount ?? 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm" oninput="recalculate()">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700">Purchase Tax</label>
                                <input id="purchase_tax" name="purchase_tax" value="{{ old('purchase_tax', $purchase->tax_amount ?? 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm" oninput="recalculate()">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-700">Shipping Cost</label>
                                <input id="shipping_cost" name="shipping_cost" value="{{ old('shipping_cost', $purchase->shipping_cost ?? 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm" oninput="recalculate()">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-slate-700">Shipping Handling</label>
                                <select id="shipping_handling" name="shipping_handling" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white">
                                    <option value="purchase_total" @selected(old('shipping_handling', $purchase->shipping_handling ?? 'purchase_total') === 'purchase_total')>Add to purchase total only</option>
                                    <option value="divide_product_cost" @selected(old('shipping_handling', $purchase->shipping_handling ?? '') === 'divide_product_cost')>Divide shipping cost into product cost</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-5 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="font-bold text-slate-700">Total Items:</span><span id="summary_items">0.00</span></div>
                        <div class="flex justify-between"><span class="font-bold text-slate-700">Subtotal:</span><span id="summary_subtotal">Rs. 0.00</span></div>
                        <div class="flex justify-between"><span class="font-bold text-slate-700">Discount:</span><span id="summary_discount">(-) Rs. 0.00</span></div>
                        <div class="flex justify-between"><span class="font-bold text-slate-700">Purchase Tax:</span><span id="summary_tax">(+) Rs. 0.00</span></div>
                        <div class="flex justify-between"><span class="font-bold text-slate-700">Shipping Cost:</span><span id="summary_shipping">(+) Rs. 0.00</span></div>
                        <div class="pt-3 border-t border-slate-200 flex justify-between text-base"><span class="font-black text-slate-850">Grand Total:</span><span id="summary_grand" class="font-black text-blue-600">Rs. 0.00</span></div>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-xl p-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-bold text-slate-700">Payment Method <span class="text-red-500">*</span></label>
                        <select id="payment_method" name="payment_method" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm bg-white">
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', $payment->payment_method ?? 'Credit / Due') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700">Payment Amount</label>
                        <input id="payment_amount" name="payment_amount" value="{{ old('payment_amount', $purchase->paid_amount ?? 0) }}" type="number" min="0" step="0.01" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm" oninput="recalculate()">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-700">Payment Reference</label>
                        <input name="payment_reference" value="{{ old('payment_reference', $payment->reference_no ?? '') }}" class="mt-1 w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                    </div>
                    <div class="lg:col-span-3 flex justify-end gap-8 text-sm">
                        <div class="text-right"><div class="font-bold text-slate-700">Grand Total</div><div id="payment_grand" class="text-xl font-black text-blue-600">Rs. 0.00</div></div>
                        <div class="text-right"><div class="font-bold text-slate-700">Due Amount</div><div id="payment_due" class="text-xl font-black text-red-600">Rs. 0.00</div></div>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-700">Additional Notes</label>
                    <textarea name="notes" rows="4" class="mt-1 w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm">{{ old('notes', $purchase->notes ?? '') }}</textarea>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" name="save_action" value="save" class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold">Save Purchase</button>
                    <button type="submit" name="save_action" value="draft" class="px-5 py-3 bg-slate-800 hover:bg-slate-950 text-white rounded-lg text-sm font-bold">Save as Draft</button>
                    <a href="{{ route('purchases.index') }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold text-center">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @if(auth()->user()->hasPermission('suppliers.create'))
    <div id="supplierModal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/50 z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-850">Add Supplier</h3>
                <button onclick="closeSupplierModal()" class="text-slate-400 hover:text-slate-700">x</button>
            </div>
            <form method="POST" action="{{ route('purchases.suppliers.store') }}" class="p-5 space-y-4">
                @csrf
                <input name="name" required placeholder="Supplier name" class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm">
                <input name="company_name" placeholder="Company name" class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input name="email" type="email" placeholder="Email" class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm">
                    <input name="phone" placeholder="Phone" class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm">
                </div>
                <textarea name="address" rows="3" placeholder="Address" class="w-full px-3 py-2.5.5 border border-slate-200 rounded-lg text-sm"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 bg-slate-100 rounded-lg text-xs font-bold">Cancel</button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        const products = @json($products);
        const suppliers = @json($suppliers->map(fn($supplier) => ['id' => $supplier->id, 'address' => $supplier->address, 'phone' => $supplier->phone])->values());
        let rows = @json($initialItems);

        function money(value) {
            return 'Rs. ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function findProduct(id) {
            return products.find(product => Number(product.id) === Number(id));
        }

        function renderRows() {
            const body = document.getElementById('items_body');
            body.innerHTML = '';

            if (rows.length === 0) {
                body.innerHTML = '<tr><td colspan="12" class="px-3 py-8 text-center text-slate-400 font-semibold">Search and add products to this purchase.</td></tr>';
                recalculate();
                return;
            }

            rows.forEach((row, index) => {
                const product = findProduct(row.product_id) || {};
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50';
                tr.innerHTML = `
                    <td class="px-3 py-3 font-bold text-slate-500">${index + 1}</td>
                    <td class="px-3 py-3 min-w-52">
                        <input type="hidden" name="items[${index}][product_id]" value="${row.product_id}">
                        <div class="font-bold text-slate-850">${product.name || 'Product'}</div>
                        <div class="text-[10px] text-slate-400">${product.sku || ''} ${product.barcode ? ' / ' + product.barcode : ''}</div>
                    </td>
                    ${numberInput(index, 'quantity', row.quantity || 1)}
                    ${numberInput(index, 'unit_cost', row.unit_cost ?? product.cost_price ?? 0)}
                    ${numberInput(index, 'discount_percent', row.discount_percent || 0)}
                    ${numberInput(index, 'discount_amount', row.discount_amount || 0)}
                    ${numberInput(index, 'tax_percent', row.tax_percent || 0)}
                    <td class="px-3 py-3 text-right font-black text-slate-850" id="line_${index}">Rs. 0.00</td>
                    ${numberInput(index, 'profit_margin', row.profit_margin || 0)}
                    ${numberInput(index, 'retail_price', row.retail_price ?? product.retail_price ?? 0)}
                    ${numberInput(index, 'wholesale_price', row.wholesale_price ?? product.wholesale_price ?? 0)}
                    <td class="px-3 py-3 text-center"><button type="button" onclick="removeRow(${index})" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></button></td>
                `;
                body.appendChild(tr);
            });
            recalculate();
        }

        function numberInput(index, key, value) {
            return `<td class="px-3 py-3"><input type="number" min="0" step="0.01" name="items[${index}][${key}]" value="${Number(value || 0)}" class="w-24 px-2 py-2.5 border border-slate-200 rounded-lg text-right" oninput="rows[${index}]['${key}'] = this.value; recalculate();"></td>`;
        }

        function searchProducts(term) {
            const list = document.getElementById('product_results');
            const value = term.trim().toLowerCase();
            if (!value) {
                list.classList.add('hidden');
                return;
            }
            const matches = products.filter(product =>
                [product.name, product.sku, product.barcode].filter(Boolean).some(field => field.toLowerCase().includes(value))
            ).slice(0, 10);
            list.innerHTML = matches.length ? matches.map(product => `
                <button type="button" onclick="addProduct(${product.id})" class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-slate-100">
                    <div class="text-xs font-bold text-slate-850">${product.name}</div>
                    <div class="text-[10px] text-slate-500">SKU: ${product.sku || '-'} | Stock: ${product.stock || 0}</div>
                </button>
            `).join('') : '<div class="px-4 py-3 text-xs text-slate-400">No products found.</div>';
            list.classList.remove('hidden');
        }

        function handleProductSearchKey(event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const term = event.target.value.trim().toLowerCase();
            const product = products.find(item => item.barcode && item.barcode.toLowerCase() === term) || products.find(item => item.sku && item.sku.toLowerCase() === term);
            if (product) addProduct(product.id);
        }

        function addProduct(id) {
            const existing = rows.find(row => Number(row.product_id) === Number(id));
            const product = findProduct(id);
            if (!product) return;
            if (existing) {
                existing.quantity = Number(existing.quantity || 0) + 1;
            } else {
                rows.push({
                    product_id: product.id,
                    quantity: 1,
                    unit_cost: product.cost_price || 0,
                    discount_percent: 0,
                    discount_amount: 0,
                    tax_percent: 0,
                    profit_margin: 0,
                    retail_price: product.retail_price || 0,
                    wholesale_price: product.wholesale_price || 0
                });
            }
            document.getElementById('product_search').value = '';
            document.getElementById('product_results').classList.add('hidden');
            renderRows();
        }

        function removeRow(index) {
            rows.splice(index, 1);
            renderRows();
        }

        function recalculate() {
            let subtotal = 0;
            let totalItems = 0;
            rows.forEach((row, index) => {
                const qty = Number(row.quantity || 0);
                const unitCost = Number(row.unit_cost || 0);
                const gross = qty * unitCost;
                const discountPercent = Number(row.discount_percent || 0);
                const discountAmount = discountPercent > 0 ? gross * discountPercent / 100 : Math.min(Number(row.discount_amount || 0), gross);
                const tax = Math.max(gross - discountAmount, 0) * Number(row.tax_percent || 0) / 100;
                const line = Math.max(gross - discountAmount, 0) + tax;
                subtotal += line;
                totalItems += qty;
                const lineCell = document.getElementById(`line_${index}`);
                if (lineCell) lineCell.innerText = money(line);
            });

            const discountType = document.getElementById('discount_type').value;
            const discountValue = Number(document.getElementById('discount_amount').value || 0);
            const overallDiscount = discountType === 'percentage' ? subtotal * discountValue / 100 : (discountType === 'fixed' ? Math.min(discountValue, subtotal) : 0);
            const purchaseTax = Number(document.getElementById('purchase_tax').value || 0);
            const shipping = Number(document.getElementById('shipping_cost').value || 0);
            const grand = Math.max(subtotal - overallDiscount, 0) + purchaseTax + shipping;
            const paid = Math.min(Number(document.getElementById('payment_amount').value || 0), grand);
            const due = Math.max(grand - paid, 0);

            document.getElementById('summary_items').innerText = totalItems.toFixed(2);
            document.getElementById('summary_subtotal').innerText = money(subtotal);
            document.getElementById('summary_discount').innerText = '(-) ' + money(overallDiscount);
            document.getElementById('summary_tax').innerText = '(+) ' + money(purchaseTax);
            document.getElementById('summary_shipping').innerText = '(+) ' + money(shipping);
            document.getElementById('summary_grand').innerText = money(grand);
            document.getElementById('payment_grand').innerText = money(grand);
            document.getElementById('payment_due').innerText = money(due);
        }

        function fillSupplierAddress() {
            const id = Number(document.getElementById('supplier_id').value || 0);
            const supplier = suppliers.find(item => Number(item.id) === id);
            document.getElementById('supplier_address').value = supplier ? (supplier.address || supplier.phone || '') : '';
        }

        function openSupplierModal() {
            document.getElementById('supplierModal').classList.remove('hidden');
            document.getElementById('supplierModal').classList.add('flex');
        }

        function closeSupplierModal() {
            document.getElementById('supplierModal').classList.add('hidden');
            document.getElementById('supplierModal').classList.remove('flex');
        }

        fillSupplierAddress();
        renderRows();
    </script>
</x-layouts::app>
