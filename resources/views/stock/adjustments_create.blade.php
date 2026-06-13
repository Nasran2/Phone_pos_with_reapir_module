<x-layouts::app :title="__('Create Stock Adjustment')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock-adjustments.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition" title="Back to Adjustments">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-lg font-bold text-slate-800">New Stock Adjustment</h2>
        </div>
        <div></div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        
        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1">
            <span class="font-bold block">Please fix the following validation errors:</span>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('stock-adjustments.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            @csrf

            <!-- Left Form Configuration (1 column width) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-xs space-y-4 text-slate-800">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Adjustment Information</h3>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Adjustment Type *</label>
                    <select 
                        name="adjustment_type" 
                        id="adjustment_type" 
                        required 
                        onchange="recalculateAllRows()"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-850 bg-white"
                    >
                        <option value="increase">Stock Increase</option>
                        <option value="decrease">Stock Decrease</option>
                        <option value="correction">Stock Correction (Qty can be +/-)</option>
                        <option value="opening_stock">Opening Stock Update (Sets exact qty)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Date *</label>
                    <input 
                        type="date" 
                        name="adjustment_date" 
                        required 
                        value="{{ now()->toDateString() }}" 
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-850"
                    >
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Reason *</label>
                    <input 
                        type="text" 
                        name="reason" 
                        required 
                        placeholder="e.g. Stock count audit discrepancy" 
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-850"
                    >
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Attach Document (Optional)</label>
                    <input 
                        type="file" 
                        name="document" 
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                    >
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Notes</label>
                    <textarea 
                        name="notes" 
                        rows="3" 
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 text-slate-850"
                        placeholder="Extra notes about this adjustment..."
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition duration-150 shadow-md cursor-pointer text-center">
                        Submit & Approve Stock
                    </button>
                </div>
            </div>

            <!-- Right Adjusted Items Table (2 columns width) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden flex flex-col">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Products List</h3>
                    
                    <!-- Searchable selector to add products -->
                    <div class="w-full md:w-80 relative">
                        <select 
                            id="productSelector" 
                            onchange="addProductRow(this)"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 bg-white"
                        >
                            <option value="">-- Add Product to Adjust --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod['id'] }}" data-sku="{{ $prod['sku'] }}" data-stock="{{ $prod['current_stock'] }}">
                                    {{ $prod['sku'] }} - {{ $prod['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-60">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 font-bold uppercase text-[9px] tracking-wider">
                                <th class="px-6 py-3">Product details</th>
                                <th class="px-6 py-3 text-right">Current Stock</th>
                                <th class="px-6 py-3 text-right">Adjustment Qty *</th>
                                <th class="px-6 py-3 text-right">New Stock</th>
                                <th class="px-6 py-3 text-right w-16">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="adjustmentTableBody" class="divide-y divide-slate-100 text-slate-700">
                            <!-- Items added dynamically -->
                            <tr id="emptyRowPlaceholder">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                    No products selected. Select a product from the top-right menu to adjust stock.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <!-- JS Handling Dynamic Products Addition -->
    <script>
        let rowIndex = 0;

        function addProductRow(selectElement) {
            const productId = selectElement.value;
            if (!productId) return;

            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const sku = selectedOption.getAttribute('data-sku');
            const name = selectedOption.text;
            const currentStock = parseFloat(selectedOption.getAttribute('data-stock'));

            // Check if already added
            if (document.getElementById(`row-${productId}`)) {
                Swal.fire({
                    title: 'Already Added',
                    text: 'This product is already in the adjustment list.',
                    icon: 'info',
                    confirmButtonColor: '#3b82f6'
                });
                selectElement.value = '';
                return;
            }

            // Remove placeholder
            const placeholder = document.getElementById('emptyRowPlaceholder');
            if (placeholder) {
                placeholder.remove();
            }

            const tbody = document.getElementById('adjustmentTableBody');
            const tr = document.createElement('tr');
            tr.id = `row-${productId}`;
            tr.dataset.currentStock = currentStock;
            tr.className = 'hover:bg-slate-50/50 transition';
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <input type="hidden" name="items[${rowIndex}][product_id]" value="${productId}">
                    <div class="font-bold text-slate-800">${name}</div>
                    <div class="text-[10px] text-slate-400 font-mono">${sku}</div>
                </td>
                <td class="px-6 py-4 text-right font-medium text-slate-500">
                    ${currentStock.toFixed(0)}
                </td>
                <td class="px-6 py-4 text-right">
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01"
                        name="items[${rowIndex}][quantity]" 
                        class="qty-input w-24 px-2.5 py-1.5 border border-slate-200 rounded text-right focus:outline-none focus:ring-1 focus:ring-blue-500 font-semibold"
                        value="1" 
                        oninput="calculateRow(this.closest('tr'))"
                        required
                    >
                </td>
                <td class="px-6 py-4 text-right">
                    <input 
                        type="text" 
                        class="new-stock-display w-24 px-2.5 py-1.5 bg-slate-50 border border-slate-150 rounded text-right font-bold text-slate-800 focus:outline-none"
                        value="${(currentStock + 1).toFixed(0)}"
                        readonly
                    >
                </td>
                <td class="px-6 py-4 text-right">
                    <button 
                        type="button" 
                        onclick="removeRow(this, ${productId})"
                        class="p-1.5 text-slate-450 hover:text-red-600 hover:bg-red-50 rounded transition duration-150 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            rowIndex++;

            // Recalculate row just added
            calculateRow(tr);

            // Reset select
            selectElement.value = '';
        }

        function removeRow(btn, productId) {
            btn.closest('tr').remove();
            
            // If empty, append placeholder back
            const tbody = document.getElementById('adjustmentTableBody');
            if (tbody.children.length === 0) {
                const tr = document.createElement('tr');
                tr.id = 'emptyRowPlaceholder';
                tr.innerHTML = `
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
                        No products selected. Select a product from the top-right menu to adjust stock.
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }

        function calculateRow(row) {
            const currentStock = parseFloat(row.dataset.currentStock);
            const inputQty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const typeSelect = document.getElementById('adjustment_type').value;

            let newStock = currentStock;
            if (typeSelect === 'increase') {
                newStock = currentStock + inputQty;
            } else if (typeSelect === 'decrease') {
                newStock = currentStock - inputQty;
            } else if (typeSelect === 'correction') {
                newStock = currentStock + inputQty; // corrected quantity can be + or -
            } else if (typeSelect === 'opening_stock') {
                newStock = inputQty;
            }

            row.querySelector('.new-stock-display').value = newStock.toFixed(0);
        }

        function recalculateAllRows() {
            const tbody = document.getElementById('adjustmentTableBody');
            Array.from(tbody.children).forEach(row => {
                if (row.id !== 'emptyRowPlaceholder') {
                    calculateRow(row);
                }
            });
        }
    </script>
</x-layouts::app>
