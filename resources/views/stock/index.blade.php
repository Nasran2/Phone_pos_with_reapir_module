<x-layouts::app :title="__('Inventory Stock')">
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800">Inventory Stock</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">
                {{ $products->total() }} Products Listed
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('stock.adjustment.create'))
            <a href="{{ route('stock-adjustments.create') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Stock Adjustment</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        
        <!-- Summary Cards Group -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Products -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Product Skus</div>
                    <div class="text-xl font-extrabold text-slate-850 mt-0.5">{{ $summary['total_products'] }}</div>
                </div>
            </div>

            <!-- Stock Value (Cost price basis) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-blue-50 text-blue-650 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Stock Valuation (Cost)</div>
                    <div class="text-xl font-extrabold text-blue-650 mt-0.5">Rs. {{ number_format($summary['stock_value'], 2) }}</div>
                </div>
            </div>

            <!-- Low Stock Warnings -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Low Stock Warnings</div>
                    <div class="text-xl font-extrabold text-amber-600 mt-0.5">{{ $summary['low_stock_items'] }}</div>
                </div>
            </div>

            <!-- Out of Stock Warnings -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex items-center gap-4">
                <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Out of Stock Items</div>
                    <div class="text-xl font-extrabold text-rose-650 mt-0.5">{{ $summary['out_of_stock_items'] }}</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('stock.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <!-- Search bar -->
                <div class="relative md:col-span-2">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search product name, SKU, barcode..." 
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <select 
                        name="category_id" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Stock Alerts Status Filter -->
                <div>
                    <select 
                        name="stock_status" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Stock Levels</option>
                        <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock Alerts</option>
                        <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('stock.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Products Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Product Name</th>
                            <th class="px-6 py-4">SKU Code</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Brand</th>
                            <th class="px-6 py-4 text-center">Current Stock</th>
                            <th class="px-6 py-4 text-right">Cost Price</th>
                            <th class="px-6 py-4 text-right">Retail Price</th>
                            <th class="px-6 py-4 text-right">Wholesale Price</th>
                            <th class="px-6 py-4 text-right">Stock Value</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($products as $product)
                        @php
                            $qty = (float) ($product->stock->quantity ?? 0);
                            $isOutOfStock = $qty <= 0;
                            $isLowStock = !$isOutOfStock && ($qty <= (float) $product->stock_alert_quantity);
                            $valCost = $qty * (float) $product->cost_price;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-bold text-slate-800">
                                {{ $product->name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-[11px] text-slate-500">
                                {{ $product->sku }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $product->category->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $product->brand->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded font-extrabold text-[11px]
                                    @if($isOutOfStock) bg-red-150 text-red-800 bg-red-100
                                    @elseif($isLowStock) bg-amber-150 text-amber-800 bg-amber-100
                                    @else bg-blue-50 text-blue-700 @endif
                                ">
                                    {{ number_format($qty, 0) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-600">
                                Rs. {{ number_format($product->cost_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-800">
                                Rs. {{ number_format($product->retail_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-600">
                                Rs. {{ number_format($product->wholesale_price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800">
                                Rs. {{ number_format($valCost, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($isOutOfStock)
                                <span class="px-2.5 py-0.5 bg-red-100 text-red-800 rounded-full font-bold text-[9px] uppercase tracking-wider">Out of Stock</span>
                                @elseif($isLowStock)
                                <span class="px-2.5 py-0.5 bg-amber-100 text-amber-800 rounded-full font-bold text-[9px] uppercase tracking-wider">Low Stock</span>
                                @else
                                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[9px] uppercase tracking-wider">Normal</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(auth()->user()->hasPermission('stock.history'))
                                <button 
                                    onclick="openHistoryModal({{ $product->id }})"
                                    class="p-1 px-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded text-[10px] font-bold transition duration-150 cursor-pointer inline-flex items-center gap-1 shadow-xs"
                                    title="View Ledger"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                    <span>History</span>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No products matching the selected filters.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if($products->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $products->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- STOCK HISTORY LOG MODAL -->
    <div id="historyModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-3xl w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-slate-800">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850">Product Stock Movement Ledger</h3>
                <button onclick="closeModal('historyModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-150 flex justify-between items-center text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[9px]">Product SKU / Name</span>
                        <span class="font-bold text-slate-800" id="hist_prod_name">-</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[9px]">Available Stock</span>
                        <span class="font-bold text-blue-650" id="hist_prod_stock">0.00</span>
                    </div>
                </div>
                <div class="overflow-x-auto border border-slate-150 rounded-xl overflow-hidden max-h-96">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-150 text-slate-650 font-bold uppercase text-[9px] tracking-wider sticky top-0 z-10">
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Movement Type</th>
                                <th class="px-4 py-3 text-right">Before</th>
                                <th class="px-4 py-3 text-right">Change</th>
                                <th class="px-4 py-3 text-right">After</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Done By</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="divide-y divide-slate-100 text-slate-700">
                            <!-- Rows loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- UI Interaction Script -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openHistoryModal(productId) {
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 font-semibold">Loading movement logs...</td></tr>`;
            
            openModal('historyModal');
            
            fetch(`{{ url('/stock/history') }}/${productId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async res => {
                    const data = await res.json().catch(() => ({
                        success: false,
                        message: `HTTP ${res.status}`,
                    }));

                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Failed to retrieve stock logs.');
                    }

                    return data;
                })
                .then(data => {
                    document.getElementById('hist_prod_name').innerText = `${data.product.sku} - ${data.product.name}`;
                    document.getElementById('hist_prod_stock').innerText = parseFloat(data.product.current_stock).toFixed(2);
                    
                    tbody.innerHTML = '';
                    if (data.histories.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No stock movement logs found for this product.</td></tr>`;
                        return;
                    }
                    
                    data.histories.forEach(h => {
                        const quantityChange = parseFloat(h.quantity_change);
                        const changeClass = quantityChange > 0 ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
                        const changeSign = quantityChange > 0 ? `+${quantityChange}` : quantityChange;
                        
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50/50 transition';
                        tr.innerHTML = `
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${h.date}</td>
                            <td class="px-4 py-3"><span class="px-1.5 py-0.5 rounded bg-slate-100 text-[9px] font-extrabold uppercase">${h.type}</span></td>
                            <td class="px-4 py-3 text-right text-slate-500">${parseFloat(h.quantity_before).toFixed(0)}</td>
                            <td class="px-4 py-3 text-right ${changeClass}">${changeSign}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">${parseFloat(h.new_quantity).toFixed(0)}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <div class="font-bold">${h.reference_no}</div>
                                <div class="text-[10px] text-slate-450">${h.description}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${h.creator}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-rose-600 font-semibold">${err.message || 'Failed to retrieve stock logs.'}</td></tr>`;
                });
        }
    </script>
</x-layouts::app>
