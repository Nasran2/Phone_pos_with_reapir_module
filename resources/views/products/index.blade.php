@php
$defaultUnit = $units->first(fn($unit) => strtolower($unit->short_name) === 'pcs')
?: $units->first(fn($unit) => str_contains(strtolower($unit->name), 'piece'))
?: $units->first();
@endphp

<x-layouts::app :title="__('Products Management')">
    <!-- SweetAlert2 CDN for modern popup alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black dark:text-white">All Products</h2>
            <span class="text-sm bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $products->total() }} Products
            </span>
        </div>
        <div class="flex items-center gap-2">
            <!-- Export / Import Buttons -->
            <a href="{{ route('products.export') }}" class="flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-black text-sm font-bold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export CSV</span>
            </a>

            <button onclick="openModal('importModal')" class="flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-black text-sm font-bold rounded-lg transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>Import Excel / CSV</span>
            </button>

            @if(auth()->user()->hasPermission('products.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>Add Product</span>
            </button>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <!-- Success & Error Messages -->
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-sm font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-sm font-medium space-y-1">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Search & Filter Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 md:grid-cols-4 gap-4">
                <!-- Search box -->
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search Name, SKU, IMEI..."
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-black transition" />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-black">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </div>
                </div>

                <!-- Store filter -->
                <div class="relative" id="filter-stores-container">
                    @php
                    $selectedStores = request('store_ids', []);
                    if (request('store_id')) $selectedStores[] = request('store_id');
                    $selectedText = count($selectedStores) > 0 ? \App\Models\Store::whereIn('id', $selectedStores)->pluck('name')->join(', ') : 'All Stores';
                    @endphp
                    <div class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white flex justify-between items-center cursor-pointer h-[42px]" onclick="document.getElementById('filter-store-dropdown').classList.toggle('hidden')">
                        <span id="filter-store-select-text" class="text-black truncate block mr-2 text-sm">
                            {{ $selectedText }}
                        </span>
                        <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>

                    <div id="filter-store-dropdown" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl hidden">
                        <div class="p-2 border-b border-slate-100">
                            <input type="text" id="filter-store-search" placeholder="Search stores..." class="w-full px-2 py-1.5 border border-slate-900 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onkeyup="filterFilterStores()">
                        </div>
                        <div class="max-h-40 overflow-y-auto p-1" id="filter-store-list">
                            @foreach($stores as $st)
                            <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 cursor-pointer rounded filter-store-item">
                                <input type="checkbox" name="store_ids[]" value="{{ $st->id }}" {{ in_array($st->id, $selectedStores) ? 'checked' : '' }} class="filter-store-checkbox rounded border-slate-900 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5" onchange="updateFilterStoreSelection()">
                                <span class="text-sm font-semibold text-black filter-store-name">{{ $st->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Category filter -->
                <div>
                    <select name="category_id" data-searchable="false" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-black bg-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Brand filter -->
                <div>
                    <select name="brand_id" data-searchable="false" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-black bg-white">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status filter -->
                <div>
                    <select name="status" data-searchable="false" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-black bg-white">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-950 text-white font-bold rounded-lg text-sm transition duration-150 cursor-pointer">Filter</button>
                    <a href="{{ route('products.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-black font-bold rounded-lg text-sm text-center transition duration-150 cursor-pointer">Clear</a>
                </div>
            </form>
        </div>

        <!-- Products Table List -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden w-full">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4">SKU / Barcode</th>
                            <th class="px-6 py-4">Category / Brand</th>
                            <th class="px-6 py-4">Cost Price</th>
                            <th class="px-6 py-4">Retail Price</th>
                            @if(\App\Support\Features::wholesaleEnabled())
                            <th class="px-6 py-4">Wholesale Price</th>
                            @endif
                            <th class="px-6 py-4">Available Stock</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-black text-sm">
                        @forelse($products as $product)
                        <tr class="hover:bg-slate-50/50 transition">
                            <!-- Image & Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" class="h-10 w-10 object-cover rounded-md border border-slate-200" alt="{{ $product->name }}">
                                    @else
                                    <div class="h-10 w-10 bg-slate-100 text-black font-bold flex items-center justify-center rounded-md border border-slate-200 text-sm">
                                        PH
                                    </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-black">{{ $product->name }}</div>
                                        <div class="text-[10px] text-black">{{ $product->unit->name ?? 'pcs' }}</div>
                                    </div>
                                </div>
                            </td>
                            <!-- SKU / Barcode -->
                            <td class="px-6 py-4 font-mono text-[10px] space-y-0.5">
                                <div><span class="text-black">SKU:</span> {{ $product->sku }}</div>
                                <div><span class="text-black">BC:</span> {{ $product->barcode }}</div>
                            </td>
                            <!-- Category / Brand -->
                            <td class="px-6 py-4 text-black">
                                <div>{{ $product->category->name ?? '-' }}</div>
                                <div class="text-[10px] font-semibold text-black">{{ $product->brand->name ?? '-' }}</div>
                            </td>
                            <!-- Cost Price -->
                            <td class="px-6 py-4 text-black font-semibold">
                                {{ \App\Support\SystemSettings::money((float) $product->cost_price) }}
                            </td>
                            <!-- Retail Price -->
                            <td class="px-6 py-4 text-blue-600 font-bold">
                                {{ \App\Support\SystemSettings::money((float) $product->retail_price) }}
                            </td>
                            {{-- Wholesale Price --}}
                            @if(\App\Support\Features::wholesaleEnabled())
                            <td class="px-6 py-4 text-emerald-600 font-bold">
                                {{ \App\Support\SystemSettings::money((float) $product->wholesale_price) }}
                            </td>
                            @endif
                            <!-- Stock -->
                            <td class="px-6 py-4">
                                @php
                                $qty = (float) $product->available_stock;
                                $heldQty = (float) $product->held_stock;
                                $formatQty = static fn ($quantity) => rtrim(rtrim(number_format((float) $quantity, 2), '0'), '.');
                                $alertQty = intval($product->stock_alert_quantity);
                                $isLow = $qty <= $alertQty;
                                    @endphp
                                    <div class="space-y-1">
                                    @if($product->storeStocks && count($product->storeStocks) > 1)
                                    <div class="mb-1 space-y-0.5">
                                        @foreach($product->storeStocks as $storeStock)
                                        <div class="border-b border-slate-100 pb-1">
                                        <div class="text-sm text-black font-semibold flex justify-between gap-2">
                                            <span>{{ $storeStock->store->name ?? 'Store' }}:</span>
                                            <span class="{{ $storeStock->available_quantity <= $alertQty ? 'text-red-600 font-bold' : 'text-black' }}">
                                                {{ $formatQty($storeStock->available_quantity) }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] font-semibold text-amber-700">Hold: {{ $formatQty($storeStock->held_quantity) }} {{ $product->unit->short_name ?? 'pcs' }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @elseif($product->storeStocks && count($product->storeStocks) === 1)
                                    <div class="text-sm text-black font-semibold mb-1">
                                        {{ $product->storeStocks[0]->store->name ?? 'Store' }}
                                    </div>
                                    @endif

                                    <div class="flex items-center gap-1">
                                        @if($product->storeStocks && count($product->storeStocks) > 1)
                                        <span class="text-sm font-bold text-black uppercase">Total:</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isLow ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-black' }}">
                                            {{ $formatQty($qty) }} {{ $product->unit->short_name ?? 'pcs' }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] font-semibold text-amber-700" title="Reserved for customer orders awaiting stock release">
                                        {{ $product->storeStocks->count() > 1 ? 'Total Hold:' : 'Hold:' }} {{ $formatQty($heldQty) }} {{ $product->unit->short_name ?? 'pcs' }}
                                    </div>
                                    @if($isLow)
                                    <div class="text-sm text-red-500 font-bold uppercase tracking-wider">Low Stock</div>
                                    @endif
                                    @foreach($product->variants as $variant)
                                    <div class="border-t border-slate-100 pt-1" title="Variant stock is tracked across all stores">
                                        <div class="text-[10px] font-semibold text-black">{{ collect([$variant->color, $variant->storage, $variant->ram])->filter()->join(' / ') ?: $variant->sku }} (all stores)</div>
                                        <div class="text-[10px] text-black">Available: {{ $formatQty($variant->available_quantity) }} {{ $product->unit->short_name ?? 'pcs' }}</div>
                                        <div class="text-[10px] font-semibold text-amber-700">Hold: {{ $formatQty($variant->held_quantity) }} {{ $product->unit->short_name ?? 'pcs' }}</div>
                                    </div>
                                    @endforeach
            </div>
            </td>
            <!-- Status -->
            <td class="px-6 py-4">
                @if($product->is_active)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800">Active</span>
                @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm font-bold bg-red-100 text-red-800">Inactive</span>
                @endif
            </td>
            <!-- Actions -->
            <td class="px-6 py-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                    <!-- Print Barcode -->
                    <button
                        onclick="showBarcodePrint('{{ $product->name }}', '{{ $product->barcode }}')"
                        class="p-1.5 text-black hover:text-black hover:bg-slate-100 transition rounded-lg cursor-pointer"
                        title="Print Barcode">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>

                    <!-- Stock History -->
                    <button
                        onclick="showStockHistory({{ $product->id }}, '{{ $product->name }}')"
                        class="p-1.5 text-black hover:text-black hover:bg-slate-100 transition rounded-lg cursor-pointer"
                        title="View Stock Ledger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    @if(auth()->user()->hasPermission('products.edit'))
                    <!-- Edit Details -->
                    <button
                        onclick="openEditModal({{ json_encode($product) }})"
                        class="p-1.5 text-black hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                        title="Edit Product">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    @endif

                    @if(auth()->user()->hasPermission('products.delete'))
                    <button
                        onclick="confirmDelete({{ $product->id }}, '{{ $product->name }}')"
                        class="p-1.5 text-black hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                        title="Delete Product">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    <form id="delete-form-{{ $product->id }}" method="POST" action="{{ route('products.destroy', $product->id) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>
            </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-black">
                    <svg class="w-10 h-10 mx-auto text-black mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4m0 4h.01" />
                    </svg>
                    <span class="font-bold">No products found matching query.</span>
                </td>
            </tr>
            @endforelse
            </tbody>
            </table>
        </div>

        <!-- Pagination links -->
        @if($products->hasPages())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
            {{ $products->links() }}
        </div>
        @endif
    </div>
    </div>

    <!-- PRODUCT ADD / EDIT MODAL -->
    <div id="productModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col transition-all duration-300 scale-95 opacity-0 modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-black" id="modalTitle">Add Product</h3>
                <button onclick="closeModal('productModal')" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <form id="productForm" method="POST" action="" enctype="multipart/form-data" class="flex-1 overflow-hidden flex flex-col">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <!-- Scrollable Body -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Product Name *</label>
                            <input type="text" name="name" id="prod_name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="e.g. iPhone 15 Pro Max">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Product Code / SKU</label>
                            <input type="text" name="sku" id="prod_sku" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="Leave blank to auto-generate">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Barcode</label>
                            <input type="text" name="barcode" id="prod_barcode" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="Leave blank to auto-generate">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Category</label>
                            <div class="flex gap-2">
                                <select name="category_id" id="prod_category_id" onchange="handleCategoryChange(this)" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                    <option value="" data-sales-tax="" data-purchase-tax="">Select Category</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" data-sales-tax="{{ $category->default_sales_tax_profile_id }}" data-purchase-tax="{{ $category->default_purchase_tax_profile_id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @if(auth()->user()->hasPermission('categories.create'))
                                <button type="button" onclick="openQuickModal('quickCategoryModal')" class="h-9 w-9 shrink-0 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700" title="Add category">+</button>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Brand</label>
                            <div class="flex gap-2">
                                <select name="brand_id" id="prod_brand_id" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                @if(auth()->user()->hasPermission('brands.create'))
                                <button type="button" onclick="openQuickModal('quickBrandModal')" class="h-9 w-9 shrink-0 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700" title="Add brand">+</button>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Unit</label>
                            <div class="flex gap-2">
                                <select name="unit_id" id="prod_unit_id" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected($defaultUnit && $defaultUnit->id === $unit->id)>{{ $unit->name }} ({{ $unit->short_name }})</option>
                                    @endforeach
                                </select>
                                @if(auth()->user()->hasPermission('units.create'))
                                <button type="button" onclick="openQuickModal('quickUnitModal')" class="h-9 w-9 shrink-0 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700" title="Add unit">+</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 {{ \App\Support\Features::wholesaleEnabled() ? 'md:grid-cols-4' : 'md:grid-cols-3' }} gap-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Cost Price *</label>
                            <input type="number" step="any" name="cost_price" id="prod_cost_price" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Retail Selling Price *</label>
                            <input type="number" step="any" name="retail_price" id="prod_retail_price" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="0.00">
                        </div>
                        @if(\App\Support\Features::wholesaleEnabled())
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Wholesale Selling Price *</label>
                            <input type="number" step="any" name="wholesale_price" id="prod_wholesale_price" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="0.00">
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Low Stock Alert Qty</label>
                            <input type="number" name="stock_alert_quantity" id="prod_alert_qty" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="5" value="5">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div id="assign-stores-container" class="relative">
                            <label class="block text-sm font-bold text-black mb-1.5">Assign To Stores</label>

                            <div class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white flex justify-between items-center cursor-pointer focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500" onclick="document.getElementById('store-dropdown').classList.toggle('hidden')">
                                <span id="store-select-text" class="text-black truncate block mr-2 font-semibold text-sm" style="max-width: 150px;">
                                    {{ $stores->where('is_default', true)->pluck('name')->join(', ') ?: 'Select stores...' }}
                                </span>
                                <svg class="w-4 h-4 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>

                            <div id="store-dropdown" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg hidden">
                                <div class="p-2 border-b border-slate-100">
                                    <input type="text" id="store-search" placeholder="Search stores..." class="w-full px-2 py-1.5 border border-slate-900 rounded text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" onkeyup="filterStores()">
                                </div>
                                <div class="max-h-40 overflow-y-auto p-1" id="store-list">
                                    @foreach($stores as $st)
                                    <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 cursor-pointer rounded store-item">
                                        <input type="checkbox" name="store_ids[]" value="{{ $st->id }}" {{ (count($stores) == 1 || $st->id == \App\Models\Setting::get('default_store_id') || $st->is_default) ? 'checked' : '' }} class="store-checkbox rounded border-slate-900 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5" onchange="updateStoreSelection()">
                                        <span class="text-sm font-semibold text-black store-name">{{ $st->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div id="opening-stock-container">
                            <label class="block text-sm font-bold text-black mb-1.5">Opening Stock Qty</label>
                            <div id="dynamic-opening-stock-inputs" class="space-y-1.5 max-h-40 overflow-y-auto">
                                <!-- Inputs generated by JS -->
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Product Image</label>
                            <div class="flex items-center gap-2">
                                <input type="file" name="logo" onchange="previewProductImage(this)" class="w-full text-[10px] text-black file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-slate-100 file:text-black hover:file:bg-slate-200 cursor-pointer">
                                <img id="prod_image_preview" src="#" class="hidden h-10 w-10 object-cover rounded-md border border-slate-200 bg-slate-50 shrink-0" alt="Image preview">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-black mb-1.5">Product Description</label>
                        <textarea name="description" id="prod_description" rows="2" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="Enter product specifics, warranty details..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Sales Tax Profile</label>
                            <select name="sales_tax_profile_id" id="prod_sales_tax_profile_id" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                <option value="">Category Default</option>
                                @foreach($taxProfiles as $tax)
                                @if($tax->tax_type == 'VAT')
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ floatval($tax->rate) }}%)</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Purchase Tax Profile</label>
                            <select name="purchase_tax_profile_id" id="prod_purchase_tax_profile_id" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                <option value="">Category Default</option>
                                @foreach($taxProfiles as $tax)
                                @if($tax->tax_type == 'VAT')
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ floatval($tax->rate) }}%)</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1.5">Tax Class Override</label>
                            <select name="tax_classification" id="prod_tax_classification" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white">
                                <option value="">Category Default</option>
                                <option value="Standard">Standard</option>
                                <option value="Zero Rated">Zero Rated</option>
                                <option value="Exempt">Exempt</option>
                                <option value="Out of Scope">Out of Scope</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1.5">
                        <input type="checkbox" name="is_active" id="prod_is_active" value="1" checked class="rounded border-slate-350 text-blue-600 focus:ring-blue-500 h-4 w-4">
                        <label for="prod_is_active" class="text-sm font-bold text-black select-none cursor-pointer">Active and available for checkout billing</label>
                    </div>

                    <!-- PHONE-SPECIFIC IMEI SERIAL SECTION (Accordion styled) -->
                    <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <h4 class="text-sm font-bold text-black flex items-center gap-1.5">
                                <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2" />
                                    <line x1="12" y1="18" x2="12.01" y2="18" />
                                </svg>
                                <span>Phone IMEI / Serial Numbers Registration</span>
                            </h4>
                            <span class="text-sm bg-slate-150 text-black font-bold px-2 py-0.5 rounded">IMEI Tracking</span>
                        </div>
                        <div id="imei-input-container">
                            <label class="block text-sm font-bold text-black mb-1">Enter IMEIs / Serials (one per line)</label>
                            <textarea name="imeis" id="prod_imeis" rows="3" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black font-mono" placeholder="IMEI-123456789012345&#10;IMEI-987654321098765"></textarea>
                            <p class="text-sm text-black mt-1">If IMEIs are registered, the total stock quantity will automatically sync to match the number of available serials.</p>
                        </div>
                    </div>

                    <!-- VARIANTS ATTRIBUTES SECTION -->
                    <div id="variants-section" class="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <h4 class="text-sm font-bold text-black flex items-center gap-1.5">
                                <svg class="w-4.5 h-4.5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                                <span>Multi-Variant Management (RAM, Storage, Color)</span>
                            </h4>
                            <button type="button" onclick="addVariantRow()" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer flex items-center gap-1">
                                <span>+ Add Variant Row</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="text-sm font-bold text-black uppercase border-b border-slate-200">
                                        <th class="pb-2 pr-2">Color</th>
                                        <th class="pb-2 pr-2">Storage</th>
                                        <th class="pb-2 pr-2">RAM</th>
                                        <th class="pb-2 pr-2">Variant SKU *</th>
                                        <th class="pb-2 pr-2">Cost Price</th>
                                        <th class="pb-2 pr-2">Retail Price</th>
                                        @if(\App\Support\Features::wholesaleEnabled())
                                        <th class="pb-2 pr-2">Wholesale</th>
                                        @endif
                                        <th class="pb-2 pr-2">Opening Stock</th>
                                        <th class="pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody id="variants-table-body" class="divide-y divide-slate-100">
                                    <!-- Dynamic rows loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 shrink-0">
                    <button type="button" onclick="closeModal('productModal')" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-sm font-bold rounded-lg transition cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition shadow-sm cursor-pointer">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    @if(auth()->user()->hasPermission('categories.create'))
    <div id="quickCategoryModal" class="fixed inset-0 bg-slate-900/35 backdrop-blur-xs hidden items-center justify-center p-4" style="z-index: 9999;">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-black">Add Category</h3>
                <button type="button" onclick="closeQuickModal('quickCategoryModal')" class="text-black hover:text-black text-lg">x</button>
            </div>
            <form id="quickCategoryForm" onsubmit="submitQuickCategory(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Category Name *</label>
                    <input name="name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="e.g. Smartphones">
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeQuickModal('quickCategoryModal')" class="px-4 py-2 border border-slate-200 text-black text-sm font-bold rounded-lg">Cancel</button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg">Save Category</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if(auth()->user()->hasPermission('brands.create'))
    <div id="quickBrandModal" class="fixed inset-0 bg-slate-900/35 backdrop-blur-xs hidden items-center justify-center p-4" style="z-index: 9999;">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-black">Add Brand</h3>
                <button type="button" onclick="closeQuickModal('quickBrandModal')" class="text-black hover:text-black text-lg">x</button>
            </div>
            <form id="quickBrandForm" onsubmit="submitQuickBrand(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Brand Name *</label>
                    <input name="name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="e.g. Apple">
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeQuickModal('quickBrandModal')" class="px-4 py-2 border border-slate-200 text-black text-sm font-bold rounded-lg">Cancel</button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if(auth()->user()->hasPermission('units.create'))
    <div id="quickUnitModal" class="fixed inset-0 bg-slate-900/35 backdrop-blur-xs hidden items-center justify-center p-4" style="z-index: 9999;">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-black">Add Unit</h3>
                <button type="button" onclick="closeQuickModal('quickUnitModal')" class="text-black hover:text-black text-lg">x</button>
            </div>
            <form id="quickUnitForm" onsubmit="submitQuickUnit(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Unit Name *</label>
                    <input name="name" required value="Pieces" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="e.g. Pieces">
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Short Name *</label>
                    <input name="short_name" required value="pcs" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="e.g. pcs">
                </div>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeQuickModal('quickUnitModal')" class="px-4 py-2 border border-slate-200 text-black text-sm font-bold rounded-lg">Cancel</button>
                    <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg">Save Unit</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- STOCK HISTORY POPUP DIALOG -->
    <div id="stockHistoryModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col max-h-[80vh] modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-black">Stock Movement Ledger</h3>
                <button onclick="closeModal('stockHistoryModal')" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="text-sm text-black mb-4 bg-slate-50 border border-slate-100 p-3 rounded-lg flex items-center justify-between">
                    <div>Product Name: <span class="font-bold text-black" id="hist_product_name"></span></div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-sm font-bold text-blackr border-b border-slate-200">
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3">Transaction</th>
                                <th class="px-4 py-3">Variant Details</th>
                                <th class="px-4 py-3 text-center">Change</th>
                                <th class="px-4 py-3 text-center">Balance</th>
                                <th class="px-4 py-3">User</th>
                            </tr>
                        </thead>
                        <tbody id="hist-table-body" class="divide-y divide-slate-100">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- BARCODE PRINT POPUP MODAL -->
    <div id="barcodePrintModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full overflow-hidden flex flex-col modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-black">Label Barcode Sticker</h3>
                <button onclick="closeModal('barcodePrintModal')" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <div class="p-6 flex flex-col items-center justify-center space-y-4">
                <div id="barcode-sticker" class="border border-dashed border-slate-350 p-6 rounded-lg bg-white flex flex-col items-center justify-center space-y-2">
                    <div class="text-sm font-bold text-black" id="bc_label_name"></div>
                    <!-- Mockup SVG Barcode -->
                    <div class="w-48 h-12 bg-slate-900 flex items-center justify-center text-white text-[8px] font-mono select-none flex-col py-1">
                        <div class="flex items-center gap-0.5 justify-center w-full px-4 mb-1">
                            <span class="h-6 w-1 bg-white"></span>
                            <span class="h-6 w-0.5 bg-white"></span>
                            <span class="h-6 w-1.5 bg-white"></span>
                            <span class="h-6 w-0.5 bg-white"></span>
                            <span class="h-6 w-1 bg-white"></span>
                            <span class="h-6 w-2 bg-white"></span>
                            <span class="h-6 w-0.5 bg-white"></span>
                            <span class="h-6 w-1.5 bg-white"></span>
                            <span class="h-6 w-1 bg-white"></span>
                            <span class="h-6 w-0.5 bg-white"></span>
                            <span class="h-6 w-2 bg-white"></span>
                        </div>
                        <span id="bc_label_code"></span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-sm text-black font-bold">Qty to Print: <input type="number" value="1" class="w-12 border rounded px-1.5 py-0.5 text-center"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 shrink-0">
                <button type="button" onclick="closeModal('barcodePrintModal')" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-sm font-bold rounded-lg cursor-pointer">Close</button>
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm cursor-pointer flex items-center gap-1.5">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print Label</span>
                </button>
            </div>
        </div>
    </div>

    <!-- PRODUCT CSV IMPORT POPUP MODAL -->
    <div id="importModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full overflow-hidden flex flex-col modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-black">Import Products</h3>
                <button onclick="closeModal('importModal')" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div class="text-sm text-black leading-relaxed space-y-1">
                    <div>Download the Excel template to see the current products and separate category, brand, and unit reference sheets.</div>
                    <a href="{{ route('products.import-template') }}" class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 font-bold text-white hover:bg-emerald-700">Download Excel Template</a>
                    <div class="font-bold">Columns structure:</div>
                    <div class="font-mono bg-slate-50 border border-slate-100 p-1.5 rounded select-all break-words">
                        {{ implode(', ', (new \App\Exports\ProductImportProductsSheet(collect()))->headings()) }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1.5">Select Spreadsheet File</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required class="w-full text-sm text-black file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-slate-100 file:text-black hover:file:bg-slate-200 cursor-pointer">
                </div>
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal('importModal')" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-sm font-bold rounded-lg cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm cursor-pointer">Start Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UI Logic Scripts -->
    <script>
        let variantIndex = 0;
        const defaultUnitId = "{{ $defaultUnit?->id }}";

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

        function openQuickModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeQuickModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function appendAndSelectOption(selectId, value, label) {
            const select = document.getElementById(selectId);
            let option = Array.from(select.options).find(existing => existing.value === String(value));
            if (!option) {
                option = new Option(label, value, true, true);
                select.add(option);
            }
            select.value = value;
        }

        function quickStore(url, form, onSuccess) {
            const submitButton = form.querySelector('button[type="submit"], button:not([type])');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
            }

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: new FormData(form),
                })
                .then(async response => {
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const message = payload.message || Object.values(payload.errors || {}).flat().join('\n') || 'Unable to save.';
                        throw new Error(message);
                    }
                    return payload;
                })
                .then(onSuccess)
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to save',
                        text: error.message,
                        confirmButtonColor: '#2563eb',
                    });
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                });
        }

        function submitQuickCategory(event) {
            event.preventDefault();
            quickStore("{{ route('categories.store') }}", event.target, data => {
                appendAndSelectOption('prod_category_id', data.category.id, data.category.name);
                event.target.reset();
                closeQuickModal('quickCategoryModal');
            });
        }

        function submitQuickBrand(event) {
            event.preventDefault();
            quickStore("{{ route('brands.store') }}", event.target, data => {
                appendAndSelectOption('prod_brand_id', data.brand.id, data.brand.name);
                event.target.reset();
                closeQuickModal('quickBrandModal');
            });
        }

        function submitQuickUnit(event) {
            event.preventDefault();
            quickStore("{{ route('units.store') }}", event.target, data => {
                appendAndSelectOption('prod_unit_id', data.unit.id, `${data.unit.name} (${data.unit.short_name})`);
                event.target.reset();
                event.target.elements.name.value = 'Pieces';
                event.target.elements.short_name.value = 'pcs';
                closeQuickModal('quickUnitModal');
            });
        }

        function previewProductImage(input) {
            const preview = document.getElementById('prod_image_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openCreateModal() {
            window.isEditMode = false;
            window.currentAssignedStores = [];
            document.getElementById('modalTitle').innerText = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productForm').action = "{{ route('products.store') }}";
            document.getElementById('formMethod').value = 'POST';

            // Reset fields
            document.getElementById('prod_name').value = '';
            document.getElementById('prod_sku').value = '';
            document.getElementById('prod_barcode').value = '';
            document.getElementById('prod_category_id').value = '';
            document.getElementById('prod_brand_id').value = '';
            document.getElementById('prod_unit_id').value = defaultUnitId || '';
            document.getElementById('prod_cost_price').value = '';
            document.getElementById('prod_retail_price').value = '';
            @if(\App\Support\Features::wholesaleEnabled())document.getElementById('prod_wholesale_price').value = '';@endif
            document.getElementById('prod_alert_qty').value = '5';
            document.getElementById('prod_sales_tax_profile_id').value = '';
            document.getElementById('prod_purchase_tax_profile_id').value = '';
            document.getElementById('prod_tax_classification').value = '';
            let dynamicInputs = document.getElementById('dynamic-opening-stock-inputs');
            if (dynamicInputs) dynamicInputs.innerHTML = '';
            updateStoreSelection();
            document.getElementById('prod_description').value = '';
            document.getElementById('prod_is_active').checked = true;
            document.getElementById('prod_imeis').value = '';

            document.getElementById('opening-stock-container').classList.remove('hidden');
            document.getElementById('assign-stores-container').classList.remove('hidden');
            document.getElementById('variants-section').classList.remove('hidden');
            document.getElementById('imei-input-container').classList.remove('hidden');

            // Clear preview
            const preview = document.getElementById('prod_image_preview');
            preview.src = '#';
            preview.classList.add('hidden');

            // Clear variants
            document.getElementById('variants-table-body').innerHTML = '';
            variantIndex = 0;
            syncOpeningStockVisibility();

            openModal('productModal');
        }

        function openEditModal(product) {
            document.getElementById('modalTitle').innerText = `Edit Product Details: ${product.name}`;
            document.getElementById('productForm').action = `/products/${product.id}`;
            document.getElementById('formMethod').value = 'PUT';

            // Populate fields
            document.getElementById('prod_name').value = product.name;
            document.getElementById('prod_sku').value = product.sku;
            document.getElementById('prod_barcode').value = product.barcode;
            document.getElementById('prod_category_id').value = product.category_id || '';
            document.getElementById('prod_brand_id').value = product.brand_id || '';
            document.getElementById('prod_unit_id').value = product.unit_id || '';
            document.getElementById('prod_cost_price').value = product.cost_price;
            document.getElementById('prod_retail_price').value = product.retail_price;
            @if(\App\Support\Features::wholesaleEnabled())document.getElementById('prod_wholesale_price').value = product.wholesale_price;@endif
            document.getElementById('prod_alert_qty').value = product.stock_alert_quantity;
            document.getElementById('prod_sales_tax_profile_id').value = product.sales_tax_profile_id || '';
            document.getElementById('prod_purchase_tax_profile_id').value = product.purchase_tax_profile_id || '';
            document.getElementById('prod_tax_classification').value = product.tax_classification || '';
            document.getElementById('prod_description').value = product.description || '';
            document.getElementById('prod_is_active').checked = !!product.is_active;

            // Populate Store Availability checkboxes
            const storeStocks = product.store_stocks || [];
            const assignedStoreIds = storeStocks.map(s => String(s.store_id));
            window.isEditMode = true;
            window.currentAssignedStores = assignedStoreIds;
            window.currentStoreStocks = storeStocks;

            // Check the appropriate checkboxes
            const checkboxes = document.querySelectorAll('.store-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = assignedStoreIds.includes(String(cb.value));
            });
            updateStoreSelection();

            // Allow managing stores on edit
            document.getElementById('assign-stores-container').classList.remove('hidden');

            document.getElementById('variants-section').classList.remove('hidden');
            document.getElementById('imei-input-container').classList.add('hidden');

            document.getElementById('variants-table-body').innerHTML = '';
            variantIndex = 0;
            if (product.variants && product.variants.length > 0) {
                product.variants.forEach(variant => addVariantRow(variant));
            }
            syncOpeningStockVisibility();

            const preview = document.getElementById('prod_image_preview');
            if (product.image) {
                preview.src = `/storage/${product.image}`;
                preview.classList.remove('hidden');
            } else {
                preview.src = '#';
                preview.classList.add('hidden');
            }

            openModal('productModal');
        }

        function addVariantRow(variant = {}) {
            const tbody = document.getElementById('variants-table-body');
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/50 transition';
            row.id = `variant-row-${variantIndex}`;

            row.innerHTML = `
                <input type="hidden" name="variants[${variantIndex}][id]" value="${variant.id || ''}">
                <td class="py-2 pr-2"><input type="text" name="variants[${variantIndex}][color]" value="${variant.color || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="e.g. Blue"></td>
                <td class="py-2 pr-2"><input type="text" name="variants[${variantIndex}][storage]" value="${variant.storage || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="e.g. 256GB"></td>
                <td class="py-2 pr-2"><input type="text" name="variants[${variantIndex}][ram]" value="${variant.ram || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="e.g. 8GB"></td>
                <td class="py-2 pr-2"><input type="text" name="variants[${variantIndex}][sku]" value="${variant.sku || ''}" required class="w-full px-2 py-1 border rounded text-sm" placeholder="SKU-VAR-${variantIndex}"></td>
                <td class="py-2 pr-2"><input type="number" step="any" name="variants[${variantIndex}][cost_price]" value="${variant.cost_price || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="Cost"></td>
                <td class="py-2 pr-2"><input type="number" step="any" name="variants[${variantIndex}][retail_price]" value="${variant.retail_price || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="Retail"></td>
                @if(\App\Support\Features::wholesaleEnabled())<td class="py-2 pr-2"><input type="number" step="any" name="variants[${variantIndex}][wholesale_price]" value="${variant.wholesale_price || ''}" class="w-full px-2 py-1 border rounded text-sm" placeholder="Wholesale"></td>@endif
                <td class="py-2 pr-2"><input type="number" name="variants[${variantIndex}][stock_quantity]" class="w-full px-2 py-1 border rounded text-sm text-center" placeholder="0" value="${variant.stock_quantity || 0}"></td>
                <td class="py-2 text-right">
                    <button type="button" onclick="removeVariantRow(${variantIndex})" class="text-red-500 hover:text-red-700 text-sm font-bold">✕</button>
                </td>
            `;

            tbody.appendChild(row);
            variantIndex++;
            syncOpeningStockVisibility();
        }

        function removeVariantRow(index) {
            const row = document.getElementById(`variant-row-${index}`);
            if (row) row.remove();
            syncOpeningStockVisibility();
        }

        function syncOpeningStockVisibility() {
            const hasVariants = document.querySelectorAll('#variants-table-body tr').length > 0;
            const container = document.getElementById('opening-stock-container');
            const dynamicContainer = document.getElementById('dynamic-opening-stock-inputs');

            if (hasVariants) {
                if (dynamicContainer) {
                    dynamicContainer.querySelectorAll('input').forEach(input => {
                        input.value = '';
                        input.disabled = true;
                    });
                }
                container.classList.add('hidden');
            } else {
                if (dynamicContainer) {
                    dynamicContainer.querySelectorAll('input').forEach(input => {
                        input.disabled = false;
                    });
                }
                container.classList.remove('hidden');
            }
        }

        function showBarcodePrint(name, barcode) {
            document.getElementById('bc_label_name').innerText = name;
            document.getElementById('bc_label_code').innerText = barcode;
            openModal('barcodePrintModal');
        }

        function showStockHistory(productId, name) {
            document.getElementById('hist_product_name').innerText = name;

            // Clear old data
            const tbody = document.getElementById('hist-table-body');
            tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-black">Loading ledger logs...</td></tr>`;

            openModal('stockHistoryModal');

            // Fetch from API
            fetch(`/products/${productId}/stock-history`)
                .then(res => res.json())
                .then(data => {
                    tbody.innerHTML = '';
                    if (data.history.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-black">No stock movements recorded.</td></tr>`;
                        return;
                    }

                    data.history.forEach(log => {
                        const changeVal = parseFloat(log.quantity_change);
                        const changeText = changeVal > 0 ? `+${changeVal}` : changeVal;
                        const changeColor = changeVal > 0 ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold';

                        tbody.innerHTML += `
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 text-black font-mono text-[10px]">${log.created_at}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-sm font-bold bg-slate-150 text-black">${log.type}</span></td>
                                <td class="px-4 py-3 text-black text-[10px]">${log.variant}</td>
                                <td class="px-4 py-3 text-center ${changeColor}">${changeText}</td>
                                <td class="px-4 py-3 text-center text-black font-semibold">${log.new_quantity}</td>
                                <td class="px-4 py-3 text-black">${log.creator}</td>
                            </tr>
                        `;
                    });
                })
                .catch(() => {
                    tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Failed to fetch ledger logs.</td></tr>`;
                });
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Product?',
                text: `Are you sure you want to soft delete "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            });
        }

        function filterStores() {
            let input = document.getElementById('store-search').value.toLowerCase();
            let items = document.querySelectorAll('.store-item');
            items.forEach(item => {
                let text = item.querySelector('.store-name').innerText.toLowerCase();
                item.style.display = text.includes(input) ? 'flex' : 'none';
            });
        }

        function updateStoreSelection() {
            let checked = document.querySelectorAll('.store-checkbox:checked');
            let text = [];
            let stockContainer = document.getElementById('dynamic-opening-stock-inputs');

            // store current values so we don't lose them when redrawing
            let currentValues = {};
            if (stockContainer) {
                stockContainer.querySelectorAll('input').forEach(input => {
                    currentValues[input.name] = input.value;
                });
                stockContainer.innerHTML = '';
            }

            checked.forEach(cb => {
                let storeName = cb.nextElementSibling.innerText;
                text.push(storeName);

                // In edit mode, allow editing existing stock but with a warning
                if (window.isEditMode && window.currentAssignedStores && window.currentAssignedStores.includes(cb.value)) {
                    if (stockContainer && window.currentStoreStocks) {
                        let stockObj = window.currentStoreStocks.find(s => String(s.store_id) === String(cb.value));
                        let qty = stockObj ? stockObj.quantity : 0;
                        let storeId = cb.value;
                        let inputName = 'opening_stock[' + storeId + ']';

                        let div = document.createElement('div');
                        div.className = 'mb-3';
                        div.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-black w-28 truncate" title="${storeName}">${storeName}</span>
                                <input type="number" name="${inputName}" value="${qty}" class="flex-1 px-2 py-1.5 border border-amber-300 bg-amber-50 rounded-md text-sm focus:ring-1 focus:ring-amber-500 text-black" placeholder="0">
                            </div>
                            <p class="text-sm text-amber-600 mt-0.5 ml-[120px] font-medium leading-tight">⚠ Warning: Modifying this will overwrite current stock.</p>
                        `;
                        stockContainer.appendChild(div);
                    }
                    return;
                }

                if (stockContainer) {
                    let storeId = cb.value;
                    let inputName = 'opening_stock[' + storeId + ']';
                    let val = currentValues[inputName] || '';

                    let div = document.createElement('div');
                    div.className = 'flex items-center gap-2';
                    div.innerHTML = `
                        <span class="text-[10px] font-semibold text-black w-28 truncate" title="${storeName}">New: ${storeName}</span>
                        <input type="number" name="${inputName}" value="${val}" class="flex-1 px-2 py-1.5 border border-slate-900 rounded-md text-sm focus:ring-1 focus:ring-blue-500 text-black" placeholder="0">
                    `;
                    stockContainer.appendChild(div);
                }
            });

            if (checked.length === 0 && stockContainer) {
                stockContainer.innerHTML = '<p class="text-[10px] text-amber-600 italic py-1">Please select a store first to assign opening stock.</p>';
            }

            document.getElementById('store-select-text').innerText = text.length > 0 ? text.join(', ') : 'Select stores...';
        }

        document.addEventListener('click', function(event) {
            const ms = document.getElementById('assign-stores-container');
            if (ms && !ms.contains(event.target)) {
                const dropdown = document.getElementById('store-dropdown');
                if (dropdown) dropdown.classList.add('hidden');
            }
        });

        // Initialize store selection text on load
        document.addEventListener('DOMContentLoaded', updateStoreSelection);

        function filterFilterStores() {
            let input = document.getElementById('filter-store-search').value.toLowerCase();
            let items = document.querySelectorAll('.filter-store-item');
            items.forEach(item => {
                let text = item.querySelector('.filter-store-name').innerText.toLowerCase();
                item.style.display = text.includes(input) ? 'flex' : 'none';
            });
        }

        function updateFilterStoreSelection() {
            let checked = document.querySelectorAll('.filter-store-checkbox:checked');
            let text = [];
            checked.forEach(cb => {
                text.push(cb.nextElementSibling.innerText);
            });
            document.getElementById('filter-store-select-text').innerText = text.length > 0 ? text.join(', ') : 'All Stores';
        }

        document.addEventListener('click', function(event) {
            const msFilter = document.getElementById('filter-stores-container');
            if (msFilter && !msFilter.contains(event.target)) {
                const dropdown = document.getElementById('filter-store-dropdown');
                if (dropdown) dropdown.classList.add('hidden');
            }
        });

        function handleCategoryChange(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            if (selectedOption) {
                const salesTaxId = selectedOption.getAttribute('data-sales-tax');
                const purchaseTaxId = selectedOption.getAttribute('data-purchase-tax');

                if (salesTaxId) {
                    document.getElementById('prod_sales_tax_profile_id').value = salesTaxId;
                } else {
                    document.getElementById('prod_sales_tax_profile_id').value = '';
                }

                if (purchaseTaxId) {
                    document.getElementById('prod_purchase_tax_profile_id').value = purchaseTaxId;
                } else {
                    document.getElementById('prod_purchase_tax_profile_id').value = '';
                }
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('import')) {
                openModal('importModal');
            }
        });
    </script>
</x-layouts::app>
