<x-layouts::app title="New Import Shipment">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">New Import Shipment</h1>
            <p class="text-sm text-slate-500">Record a foreign supplier invoice and link to inventory</p>
        </div>
        <a href="{{ route('import-shipments.index') }}" class="text-slate-500 hover:text-slate-700 font-medium text-sm transition-colors">
            &larr; Back to Shipments
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($importShipment) ? route('import-shipments.update', $importShipment) : route('import-shipments.store') }}" id="shipmentForm" class="space-y-6">
        @csrf
        @isset($importShipment) @method('PUT') @endisset
        
        <!-- Header Details -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-100 pb-2">Shipment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reference / Invoice No *</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no', $importShipment->reference_no ?? '') }}" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Shipment Date *</label>
                    <input type="date" name="shipment_date" value="{{ old('shipment_date', $importShipment->shipment_date ?? date('Y-m-d')) }}" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Supplier *</label>
                    <select name="supplier_id" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm select2">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $importShipment->supplier_id ?? null) == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Currency *</label>
                    <select name="currency_id" id="currency_id" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm" onchange="updateExchangeRate()">
                        <option value="">Select Currency</option>
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected(old('currency_id', $importShipment->currency_id ?? null) == $currency->id) data-rate="{{ $exchangeRates[$currency->id]->rate ?? 1 }}">{{ $currency->code }} - {{ $currency->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Exchange Rate (to LKR) *</label>
                    <input type="number" step="0.000001" min="0.000001" name="exchange_rate" value="{{ old('exchange_rate', $importShipment->exchange_rate ?? '') }}" id="exchange_rate" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Receiving Store *</label>
                    <select name="store_id" required class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm select2">
                        <option value="">Select Store</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('store_id', $importShipment->store_id ?? null) == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('notes', $importShipment->notes ?? '') }}</textarea>
                </div>
            </div>
            <!-- Hidden field for cost allocation method -> fixed to 'value' for now as standard practice -->
            <input type="hidden" name="cost_allocation_method" value="value">
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Shipment Items</h2>
                <button type="button" onclick="addItemRow()" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Row
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="itemsTable">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200">
                            <th class="p-3 text-xs font-bold text-slate-500 uppercase w-1/3">Product *</th>
                            <th class="p-3 text-xs font-bold text-slate-500 uppercase w-1/4">HS Code</th>
                            <th class="p-3 text-xs font-bold text-slate-500 uppercase text-right w-1/6">Qty *</th>
                            <th class="p-3 text-xs font-bold text-slate-500 uppercase text-right w-1/6">Foreign Unit Price *</th>
                            <th class="p-3 text-xs font-bold text-slate-500 uppercase text-center w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody" class="divide-y divide-slate-100">
                        <!-- Rows injected via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('import-shipments.index') }}" class="px-6 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors shadow-sm">Save Draft</button>
        </div>
    </form>

    <script>
        const products = @json($products->map(function($p) { return ['id' => $p->id, 'name' => $p->name, 'hs_code_id' => $p->hs_code_id]; })->toArray());
        const hsCodes = @json($hsCodes->toArray());
        
        const escapeOption = value => { const span = document.createElement('span'); span.textContent = value; return span.innerHTML; };
        let rowCount = 0;

        function updateExchangeRate() {
            const select = document.getElementById('currency_id');
            const selectedOption = select.options[select.selectedIndex];
            const rate = selectedOption.getAttribute('data-rate');
            if(rate) {
                document.getElementById('exchange_rate').value = rate;
            }
        }

        function addItemRow(item = {}) {
            const tbody = document.getElementById('itemsTbody');
            const tr = document.createElement('tr');
            tr.id = `row_${rowCount}`;
            
            let productOptions = '<option value="">Select Product...</option>';
            products.forEach(p => {
                productOptions += `<option value="${p.id}" data-hscode="${p.hs_code_id || ''}">${escapeOption(p.name)}</option>`;
            });

            let hsCodeOptions = '<option value="">Select HS Code...</option>';
            hsCodes.forEach(h => {
                hsCodeOptions += `<option value="${h.id}">${escapeOption(h.code)} - ${escapeOption(h.description)}</option>`;
            });

            tr.innerHTML = `
                <td class="p-3">
                    <select name="items[${rowCount}][product_id]" required class="w-full rounded border-slate-300 text-sm p-2" onchange="productSelected(this, ${rowCount})">
                        ${productOptions}
                    </select>
                </td>
                <td class="p-3">
                    <select name="items[${rowCount}][hs_code_id]" id="hs_code_${rowCount}" class="w-full rounded border-slate-300 text-sm p-2">
                        ${hsCodeOptions}
                    </select>
                </td>
                <td class="p-3">
                    <input type="number" name="items[${rowCount}][quantity]" required min="1" step="0.01" value="1" class="w-full text-right rounded border-slate-300 text-sm p-2">
                </td>
                <td class="p-3">
                    <input type="number" name="items[${rowCount}][foreign_unit_price]" required min="0" step="0.0001" value="0.00" class="w-full text-right rounded border-slate-300 text-sm p-2">
                </td>
                <td class="p-3 text-center">
                    <button type="button" onclick="removeRow(${rowCount})" class="text-rose-500 hover:text-rose-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </td>
            `;
            
            tbody.appendChild(tr);
            Object.entries(item).forEach(([key, value]) => { const field = tr.querySelector('[name="items[' + rowCount + '][' + key + ']"]'); if (field) field.value = value ?? ''; });
            rowCount++;
        }

        function removeRow(id) {
            const row = document.getElementById(`row_${id}`);
            if(row) row.remove();
        }

        function productSelected(selectElement, rowId) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const hsCodeId = selectedOption.getAttribute('data-hscode');
            
            if(hsCodeId) {
                const hsCodeSelect = document.getElementById(`hs_code_${rowId}`);
                if(hsCodeSelect) {
                    hsCodeSelect.value = hsCodeId;
                }
            }
        }

        // Initialize with one row
        document.addEventListener('DOMContentLoaded', function() {
            const initialItems = @json(old('items', isset($importShipment) ? $importShipment->items->toArray() : []));
            if (initialItems.length) initialItems.forEach(item => addItemRow(item)); else addItemRow();
        });
    </script>
</x-layouts::app>
