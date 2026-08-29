<x-layouts::app :title="__('Create Stock Adjustment')">
    <!-- Header Section -->
    <div class="p-6 md:p-8 pb-0">
        <a href="{{ route('stock-adjustments.index') }}" class="inline-flex items-center text-xs text-slate-500 hover:text-teal-600 font-medium mb-2 transition">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Adjustments History
        </a>
        <h1 class="text-2xl font-serif text-slate-800 tracking-tight">New Stock Adjustment</h1>
        <p class="text-sm text-slate-500 mt-1">Add multiple products to adjust their stock levels simultaneously.</p>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 pt-6 flex-1 w-full overflow-y-auto">
        
        @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-medium space-y-1">
            <span class="font-bold block">Please fix the following validation errors:</span>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('stock-adjustments.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 md:p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Adjustment Type *</label>
                        <select name="adjustment_type" id="adjustment_type" required onchange="recalculateAllRows()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 bg-white">
                            <option value="correction">Stock Correction (+/-)</option>
                            <option value="increase">Stock Increase</option>
                            <option value="decrease">Stock Decrease</option>
                            <option value="opening_stock">Opening Stock Update</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Date *</label>
                        <input type="date" name="adjustment_date" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Reference / Reason</label>
                        <input type="text" name="reason" placeholder="e.g. Stock count discrepancy" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Transaction Notes (Optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500" placeholder="Overall reason or reference for this adjustment..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Add Product</label>
                    <div class="relative" id="productSearchContainer">
                        <input 
                            type="text" 
                            id="productSearchInput" 
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 bg-white" 
                            placeholder="Search by SKU or product name..."
                            autocomplete="off"
                        >
                        <div id="productSuggestions" class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                            <!-- JS will populate suggestions here -->
                        </div>
                    </div>
                </div>

                <div class="border border-slate-200/60 rounded-xl overflow-hidden mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-200/60 text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                                    <th class="px-6 py-3">Product</th>
                                    <th class="px-6 py-3">Store & Inventory</th>
                                    <th class="px-6 py-3 text-right">Unit & Qty</th>
                                    <th class="px-6 py-3 text-right">New Stock</th>
                                    <th class="px-6 py-3 text-right w-16">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="adjustmentTableBody" class="divide-y divide-slate-100 text-slate-700">
                                <!-- Items added dynamically -->
                                <tr id="emptyRowPlaceholder">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                                        No products added yet. Search and select a product above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-6 py-2.5 bg-[#84A9A6] hover:bg-[#729794] text-white font-medium rounded-lg text-sm transition shadow-sm cursor-pointer">
                        Post Adjustments
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- JS Handling Dynamic Products Addition -->
    <script>
        const allProducts = @json($products);
        let rowIndex = 0;

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearchInput');
            const suggestionsBox = document.getElementById('productSuggestions');
            let highlightIndex = -1;
            let currentSuggestions = [];

            // Prevent form submit on Enter in search box
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlightIndex > -1 && highlightIndex < currentSuggestions.length) {
                        selectProduct(currentSuggestions[highlightIndex]);
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (highlightIndex < currentSuggestions.length - 1) {
                        highlightIndex++;
                        updateHighlight();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (highlightIndex > 0) {
                        highlightIndex--;
                        updateHighlight();
                    }
                } else if (e.key === 'Escape') {
                    closeSuggestions();
                }
            });

            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                if (query.length === 0) {
                    closeSuggestions();
                    return;
                }

                currentSuggestions = allProducts.filter(p => 
                    (p.sku && p.sku.toLowerCase().includes(query)) || 
                    (p.name && p.name.toLowerCase().includes(query))
                ).slice(0, 10); // Limit to 10 results

                renderSuggestions();
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!document.getElementById('productSearchContainer').contains(e.target)) {
                    closeSuggestions();
                }
            });

            function renderSuggestions() {
                if (currentSuggestions.length === 0) {
                    suggestionsBox.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No products found</div>';
                    suggestionsBox.classList.remove('hidden');
                    return;
                }

                highlightIndex = -1;
                suggestionsBox.innerHTML = '';
                currentSuggestions.forEach((prod, index) => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-2 cursor-pointer text-sm border-b border-slate-50 last:border-0 suggestion-item';
                    div.innerHTML = `<span class="font-bold text-slate-700">${prod.sku}</span> - <span class="text-slate-600">${prod.name}</span> <span class="text-xs text-slate-400 ml-2">(Stock: ${prod.current_stock})</span>`;
                    
                    div.addEventListener('mouseenter', () => {
                        highlightIndex = index;
                        updateHighlight();
                    });
                    
                    div.addEventListener('click', () => {
                        selectProduct(prod);
                    });
                    
                    suggestionsBox.appendChild(div);
                });

                suggestionsBox.classList.remove('hidden');
            }

            function updateHighlight() {
                const items = suggestionsBox.querySelectorAll('.suggestion-item');
                items.forEach((item, index) => {
                    if (index === highlightIndex) {
                        item.classList.add('bg-teal-50', 'text-teal-700');
                        item.classList.remove('text-slate-700');
                    } else {
                        item.classList.remove('bg-teal-50', 'text-teal-700');
                    }
                });
            }

            function closeSuggestions() {
                suggestionsBox.classList.add('hidden');
                highlightIndex = -1;
            }

            function selectProduct(prod) {
                addProductRow(prod);
                searchInput.value = '';
                closeSuggestions();
                searchInput.focus();
            }
        });

        function addProductRow(prod) {
            const productId = prod.id;
            if (!productId) return;

            const sku = prod.sku;
            const name = prod.name;
            const currentStock = parseFloat(prod.current_stock);

            // Check if already added
            if (document.getElementById(`row-${productId}`)) {
                Swal.fire({
                    title: 'Already Added',
                    text: 'This product is already in the adjustment list.',
                    icon: 'info',
                    confirmButtonColor: '#0d9488'
                });
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
                    <div class="font-medium text-slate-800">${name}</div>
                    <div class="text-[10px] text-slate-400">SKU: ${sku}</div>
                </td>
                <td class="px-6 py-4 text-slate-600 text-sm">
                    Main Store<br><span class="text-[10px] text-slate-400">Current: ${currentStock.toFixed(0)}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <input 
                        type="number" 
                        step="any" 
                        name="items[${rowIndex}][quantity]" 
                        class="qty-input w-24 px-2 py-1.5 border border-slate-200 rounded text-right focus:outline-none focus:ring-1 focus:ring-teal-500 font-medium"
                        value="1" 
                        oninput="calculateRow(this.closest('tr'))"
                        required
                    >
                </td>
                <td class="px-6 py-4 text-right">
                    <input 
                        type="text" 
                        class="new-stock-display w-24 px-2 py-1.5 bg-slate-50 border border-slate-100 rounded text-right font-medium text-slate-800 focus:outline-none"
                        value="${(currentStock + 1).toFixed(0)}"
                        readonly
                    >
                </td>
                <td class="px-6 py-4 text-right">
                    <button 
                        type="button" 
                        onclick="removeRow(this, ${productId})"
                        class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded transition duration-150 cursor-pointer"
                    >
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            rowIndex++;

            // Recalculate row just added
            calculateRow(tr);
        }

        function removeRow(btn, productId) {
            btn.closest('tr').remove();
            
            // If empty, append placeholder back
            const tbody = document.getElementById('adjustmentTableBody');
            if (tbody.children.length === 0) {
                const tr = document.createElement('tr');
                tr.id = 'emptyRowPlaceholder';
                tr.innerHTML = `
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                        No products added yet. Search and select a product above.
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
