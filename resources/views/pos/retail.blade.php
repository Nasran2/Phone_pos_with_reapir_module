<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head', ['title' => __('Retail POS')])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="pos-shell h-[100dvh] max-h-[100dvh] overflow-hidden bg-slate-50 text-slate-800 antialiased font-sans flex flex-col" x-data="retailPosApp()">

    <!-- SCREEN 7: Hamburger Menu Drawer (Overlay) -->
    <div x-show="showMenuDrawer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-[min(88vw,20rem)] bg-[#0c1630] border-r border-[#182647] text-slate-300 z-50 flex flex-col justify-between shadow-2xl"
         x-cloak>
        
        <div>
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-[#182647] bg-[#091024]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                            <path d="M9 19h6" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-sm font-bold text-white leading-tight">Retail POS</h2>
                            <span class="px-1.5 py-0.5 bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 rounded-md text-[8px] font-black uppercase tracking-wider">
                                <template x-if="editSale">
                                    <span>Updating Invoice #<span x-text="editSale.id"></span></span>
                                </template>
                                <template x-if="!editSale">
                                    <span>{{ $isLorryMode ?? false ? 'Assigned Lorry' : 'Retail Mode' }}</span>
                                </template>
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">Navigation Menu</p>
                    </div>
                </div>
                <button @click="showMenuDrawer = false" class="text-slate-400 hover:text-white cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Links -->
            <nav class="p-6 space-y-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-455" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Back to Dashboard</span>
                </a>
                <a href="{{ route('pos.wholesale') }}" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-455" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="12" rx="2"/><path d="M12 15v4"/><path d="M9 19h6"/></svg>
                    <span>Wholesale POS</span>
                </a>
                <a href="#" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-455" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Report Logs</span>
                </a>
            </nav>
        </div>

        <!-- User profile details -->
        <div class="p-6 border-t border-[#182647] bg-[#091024] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white leading-none">{{ auth()->user()->name }}</h4>
                    <p class="text-xs text-slate-400 font-semibold mt-1">Administrator</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-slate-450 hover:text-red-500 cursor-pointer" title="Log Out">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Drawer Backdrop Overlay -->
    <div x-show="showMenuDrawer" 
         @click="showMenuDrawer = false" 
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 lg:hidden" 
         x-cloak></div>

    <!-- MAIN GRID CONTAINER -->
    <div class="flex-1 flex flex-col w-full h-[100dvh] overflow-hidden">
        
        <!-- TOP HEADER BAR -->
        <header class="bg-[#0c1630] border-b border-[#182647] text-white min-h-16 h-auto px-3 sm:px-4 py-2 sm:py-0 flex flex-wrap items-center justify-between gap-2 shrink-0 z-30">
            <div class="flex min-w-0 items-center gap-2 sm:gap-4">
                <button @click="showMenuDrawer = true" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-350 hover:text-white cursor-pointer" title="Toggle Navigation" aria-label="Open navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 shadow-md shadow-blue-500/10">
                        <svg class="w-5.5 h-5.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-sm font-black tracking-wide leading-none">Retail POS</h1>
                            <span class="hidden sm:inline-flex px-1.5 py-0.5 bg-blue-500/20 text-blue-400 border border-blue-500/30 rounded-md text-[8px] font-black uppercase tracking-wider">Retail Mode</span>
                        </div>
                        <p class="hidden sm:block text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Phone Shop POS / ERP</p>
                    </div>
                </div>
            </div>

            <!-- Running digital clock -->
            <div class="hidden sm:flex items-center gap-2 bg-[#091024] border border-[#182647] px-3.5 py-1 rounded-xl">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-300 font-mono" x-text="liveTime"></span>
            </div>

                <div class="flex w-full sm:w-auto min-w-0 items-center justify-end gap-1.5 sm:gap-3 overflow-x-auto scrollbar-hide">
                    @if($enableMultiStore ?? false)
                    <div class="flex items-center gap-2 mr-2">
                        <select onchange="window.location.href='{{ route('pos.retail') }}?store_id=' + this.value" class="bg-[#091024] border border-[#182647] text-slate-300 text-xs font-bold rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500">
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ ($activeStoreId ?? 1) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($distributionEnabled ?? false && ($lorries ?? collect())->isNotEmpty())
                    <div class="flex items-center gap-2 mr-2">
                        <select onchange="window.location.href='{{ route('pos.retail') }}?source_type=' + this.value + '&store_id={{ $activeStoreId ?? 1 }}'" class="bg-[#091024] border border-[#182647] text-slate-300 text-xs font-bold rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500" title="Retail stock source">
                            <option value="store" {{ ($sourceType ?? 'store') === 'store' ? 'selected' : '' }}>Store Stock</option>
                            <option value="lorry" {{ ($sourceType ?? 'store') === 'lorry' ? 'selected' : '' }}>Lorry Stock</option>
                        </select>
                        @if(($sourceType ?? 'store') === 'lorry')
                        <select onchange="window.location.href='{{ route('pos.retail') }}?source_type=lorry&lorry_id=' + this.value + '&store_id={{ $activeStoreId ?? 1 }}'" class="bg-[#091024] border border-[#182647] text-slate-300 text-xs font-bold rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500" title="Select lorry">
                            <option value="">Select Lorry</option>
                            @foreach($lorries as $lorry)
                                <option value="{{ $lorry->id }}" {{ (string) ($activeLorryId ?? '') === (string) $lorry->id ? 'selected' : '' }}>{{ $lorry->lorry_no }}</option>
                            @endforeach
                        </select>
                        @if(($distributionTrips ?? collect())->isNotEmpty())
                        <select onchange="window.location.href='{{ route('pos.retail') }}?source_type=lorry&lorry_id={{ $activeLorryId ?? '' }}&trip_id=' + this.value + '&store_id={{ $activeStoreId ?? 1 }}'" class="bg-[#091024] border border-[#182647] text-slate-300 text-xs font-bold rounded-lg px-2 py-1.5 focus:outline-none focus:border-blue-500" title="Select trip">
                            <option value="">No Trip</option>
                            @foreach($distributionTrips as $trip)
                                <option value="{{ $trip->id }}" {{ (string) ($activeTripId ?? '') === (string) $trip->id ? 'selected' : '' }}>{{ $trip->trip_no }}</option>
                            @endforeach
                        </select>
                        @endif
                        @endif
                    </div>
                    @endif
                    <button @click="showHoldSalesModal = true" class="min-h-11 shrink-0 px-3 py-1.5 bg-amber-600/10 hover:bg-amber-600/20 text-amber-500 hover:text-amber-400 border border-amber-500/20 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Hold Invoices <span class="hidden sm:inline">(F6)</span></span>
                    </button>
                    <div class="text-right hidden md:block">
                        <p class="text-xs font-bold leading-none">{{ auth()->user()->name }}</p>
                        <span class="text-[9px] text-slate-400 uppercase font-black">Cashier</span>
                    </div>
                </div>
        </header>

        <!-- Dynamic Category Header Selector (Horizontal scroll bar) -->
        <div class="bg-white border-b border-slate-200/80 px-3 sm:px-4 py-2 flex items-center justify-between shrink-0 gap-2 sm:gap-3">
            <div class="flex min-w-0 items-center gap-2 overflow-x-auto scrollbar-hide py-1 flex-1">
                <button @click="selectedCategory = 'all'" 
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap"
                        :class="selectedCategory === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'">
                    All Products
                </button>
                @foreach($categories as $category)
                    <button @click="selectedCategory = '{{ $category->slug }}'" 
                            class="px-4 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer whitespace-nowrap"
                            :class="selectedCategory === '{{ $category->slug }}' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
            
            <!-- Mobile Toggle Tabs -->
            <div class="flex shrink-0 items-center gap-1 lg:hidden bg-slate-100 p-0.5 rounded-lg text-xs font-bold text-slate-600">
                <button @click="mobileTab = 'products'" class="min-h-10 px-2.5 sm:px-3 py-1 rounded-md" :class="mobileTab === 'products' ? 'bg-white text-blue-600 shadow-xs' : ''">Products</button>
                <button @click="mobileTab = 'cart'" class="min-h-10 px-2.5 sm:px-3 py-1 rounded-md relative" :class="mobileTab === 'cart' ? 'bg-white text-blue-600 shadow-xs' : ''">
                    <span>Cart</span>
                    <span x-show="cart.length > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] h-4.5 w-4.5 rounded-full flex items-center justify-center font-black" x-text="cart.length"></span>
                </button>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex overflow-hidden">
            
            <!-- LEFT COLUMN: SEARCH + PRODUCT GRID -->
            <div class="flex-1 overflow-y-auto p-3 sm:p-4 flex flex-col justify-between" :class="mobileTab === 'products' ? 'block' : 'hidden lg:block'">
                <div class="space-y-4">
                    <!-- Global search input -->
                    <div class="relative w-full">
                        <input 
                            type="text" 
                            placeholder="Scan Barcode or Search by Name, SKU, IMEI... (F1)" 
                            x-model="searchQuery"
                            @input="watchSearchQuery($event.target.value)"
                            id="search-input"
                            class="w-full pl-10 pr-12 py-3 bg-white border border-slate-250 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 gap-1.5">
                            <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-bold text-slate-500 bg-slate-100 border rounded-md shadow-2xs">F1</kbd>
                            <svg class="w-5 h-5 text-blue-500 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 5h2v14H3zm4 0h1v14H7zm3 0h3v14h-3zm5 0h1v14h-1zm3 0h3v14h-3z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-2 sm:gap-3">
                        <template x-for="product in filteredProducts()" :key="product.id">
                            <div @click="addToCart(product)" 
                                 class="bg-white border border-slate-250/70 rounded-xl p-2.5 flex flex-col justify-between hover:shadow-md cursor-pointer transition select-none hover:border-blue-400 group relative"
                                 :class="product.stock <= 0 ? 'opacity-50 cursor-not-allowed border-slate-200' : ''">
                                
                                <!-- Image & Stock Alert Tag -->
                                <div class="h-16 sm:h-20 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 group-hover:scale-102 transition shrink-0 relative overflow-hidden">
                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                        <line x1="8" y1="21" x2="16" y2="21"/>
                                        <line x1="12" y1="17" x2="12" y2="21"/>
                                    </svg>
                                    
                                    <!-- Stock label -->
                                    <span class="absolute top-1 right-1 px-1.5 py-0.5 text-[8px] font-black rounded-md shadow-xs text-white" 
                                          :class="product.stock <= 0 ? 'bg-red-600' : (product.stock <= product.stock_alert_quantity ? 'bg-amber-500' : 'bg-slate-900/60')"
                                          x-text="product.stock <= 0 ? 'Out of Stock' : 'Stock: ' + parseInt(product.stock)"></span>
                                </div>

                                <!-- Text Details -->
                                <div class="mt-2 space-y-0.5 w-full min-w-0">
                                    <!-- Dynamic Color badges -->
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="text-[8px] px-1 py-0.2 rounded bg-slate-100 text-slate-650 font-bold" x-text="product.category_slug === 'smartphones' ? 'Device' : 'Accessory'"></span>
                                    </div>
                                    <h4 class="text-[11px] font-bold text-slate-800 leading-tight line-clamp-2 h-7 group-hover:text-blue-600 transition" x-text="product.name"></h4>
                                    <h5 class="text-xs font-black text-blue-600 pt-0.5">Rs. <span x-text="formatMoney(product.retail_price)"></span></h5>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: CART & CUSTOMER DETAILS -->
            <div class="w-full lg:w-96 border-l border-slate-200/80 bg-white p-3 sm:p-4 flex flex-col overflow-hidden shrink-0 select-none"
                 :class="mobileTab === 'cart' ? 'flex' : 'hidden lg:flex'">
                
                <!-- Customer info header -->
                <div class="space-y-3 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <select x-model="selectedCustomerId" class="w-full pl-3 pr-8 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition">
                                <template x-for="cust in customers" :key="cust.id">
                                    <option :value="cust.id" x-text="cust.name + (cust.phone !== 'N/A' ? ' (' + cust.phone + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
                        <button @click="showAddCustomerModal = true" class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/10 cursor-pointer" title="Add Customer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Customer loyalty info if non-default customer -->
                    <template x-if="getSelectedCustomer() && getSelectedCustomer().id !== 1">
                        <div class="p-2.5 rounded-xl bg-blue-50/50 border border-blue-100 text-[10px] font-bold text-slate-600 flex items-center justify-between">
                            <span class="flex items-center gap-1"><span class="text-blue-600 font-extrabold uppercase">Loyalty:</span> <span x-text="getSelectedCustomer().points"></span> pts</span>
                            <span class="flex items-center gap-1"><span class="text-red-500 font-extrabold uppercase">Due Bal:</span> Rs. <span x-text="formatMoney(getSelectedCustomer().balance)"></span></span>
                        </div>
                    </template>

                    <!-- Cart Item Table Header -->
                    <div class="grid grid-cols-12 text-[10px] font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">
                        <span class="col-span-6">Item details</span>
                        <span class="col-span-3 text-center">Qty</span>
                        <span class="col-span-3 text-right">Total</span>
                    </div>
                </div>

                <!-- Cart Items Scroll Area -->
                <div class="flex-1 overflow-y-auto py-2 divide-y divide-slate-100/60">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="grid grid-cols-12 gap-1 items-start py-2.5">
                            
                            <!-- Product Details & Serial Selector -->
                            <div class="col-span-6 space-y-1">
                                <h4 class="text-xs font-bold text-slate-800 leading-snug truncate hover:text-blue-600 cursor-pointer transition" 
                                    @click="openEditItemModal(item, index)" 
                                    x-text="item.name"
                                    title="Click to edit item discount, qty, and price"></h4>
                                <template x-if="item.variant_label">
                                    <div class="text-[9px] font-bold text-blue-600" x-text="item.variant_label"></div>
                                </template>
                                
                                <!-- Serial/IMEI Selection (Only visible for Smartphones) -->
                                <template x-if="item.is_smartphone && item.serials.length > 0">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">IMEI / Serial</label>
                                        <select x-model="item.serial_no" class="w-full mt-0.5 px-1.5 py-0.5 bg-slate-50 border border-slate-200 rounded text-[9px] font-bold focus:outline-none text-slate-700">
                                            <template x-for="sn in item.serials" :key="sn">
                                                <option :value="sn" x-text="sn"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>
                                
                                <div class="flex items-center gap-1">
                                    <span class="text-[8px] text-slate-400 uppercase font-black">Price:</span>
                                    <input type="number" 
                                           x-model.number="item.selling_price" 
                                           @input="updateLineTotal(item)"
                                           class="w-16 px-1 py-0.5 border border-slate-200 rounded text-[9px] font-semibold focus:outline-none" />
                                </div>
                            </div>
                            
                            <!-- Qty Steppers / Manual input -->
                            <div class="col-span-3 flex flex-col items-center justify-center gap-1">
                                <div class="flex items-center gap-1">
                                    <button @click="decrementQty(item)" class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded focus:outline-none cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                    <input type="number" 
                                           x-model.number="item.quantity" 
                                           @input="clampCartQuantity(item)"
                                           class="w-10 h-8 p-0 text-center border border-slate-200 rounded text-xs font-bold focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                    <button @click="incrementQty(item)" class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded focus:outline-none cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Line subtotal / remove action -->
                            <div class="col-span-3 flex flex-col items-end justify-center">
                                <span class="text-xs font-black text-slate-800">Rs. <span x-text="formatMoney(item.line_total)"></span></span>
                                <button @click="removeFromCart(index)" class="text-[9px] text-red-500 hover:text-red-650 font-bold mt-1.5 opacity-60 hover:opacity-100 cursor-pointer">Remove</button>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 py-16">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <span class="text-xs font-bold">Your Retail POS Cart is empty</span>
                        </div>
                    </template>
                </div>

                <!-- BILL SUMMARY & CHECKOUT ACTIONS -->
                <div class="border-t border-slate-200/80 pt-4 space-y-3 shrink-0">
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="font-bold text-slate-700">Items</span>
                            <span class="font-bold text-slate-800" x-text="cart.length"></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span class="font-bold text-slate-700">Subtotal</span>
                            <span class="font-bold text-slate-800">Rs <span x-text="formatMoney(calculateSubtotal())"></span></span>
                        </div>
                        <template x-if="totals.addedTax > 0 || (totals.addedTax == 0 && totals.includedTax == 0)">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="font-bold text-slate-700">Taxable Amount</span>
                                <span class="font-bold text-slate-800">Rs <span x-text="formatMoney(totals.subTotal - totals.discount)"></span></span>
                            </div>
                        </template>
                        <template x-if="totals.addedTax > 0">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="font-bold text-slate-700">Added VAT</span>
                                <span class="font-bold text-slate-800">Rs <span x-text="formatMoney(totals.addedTax)"></span></span>
                            </div>
                        </template>
                        <template x-if="totals.includedTax > 0">
                            <div class="flex items-center justify-between text-slate-500">
                                <span class="font-bold text-slate-700">Tax Included</span>
                                <span class="font-bold text-slate-800">Rs <span x-text="formatMoney(totals.includedTax)"></span></span>
                            </div>
                        </template>
                        <div class="flex items-center justify-between text-slate-800 font-black text-sm pt-1 mt-1 border-t border-slate-200">
                            <span>Payable</span>
                            <span>Rs <span x-text="formatMoney(calculateGrandTotal())"></span></span>
                        </div>

                        <div class="flex items-center gap-2 mt-2 pt-2">
                            <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-slate-700">
                                <input type="radio" value="fixed" x-model="globalDiscountType" class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500 border-slate-300">
                                Fixed
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-slate-700">
                                <input type="radio" value="percentage" x-model="globalDiscountType" class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500 border-slate-300">
                                %
                            </label>
                            <div class="flex-1 ml-1">
                                <input type="number" step="any" min="0" placeholder="Discount" x-model.number="globalDiscountValue" class="w-full text-left px-3 py-2 border border-slate-200 rounded-lg text-xs font-bold focus:outline-none focus:border-blue-500" />
                            </div>
                        </div>
                    </div>

                    <button @click="printDueInvoice()"
                            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-3 py-3 text-xs font-black text-amber-800 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="cart.length === 0">
                        <i class="fas fa-file-invoice"></i>
                        <span>Due &amp; Print <span class="font-semibold">(No Payment)</span></span>
                    </button>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-bold pt-2.5 border-t border-slate-100">
                        <button @click="clearCart()" class="py-3 bg-red-50 text-red-600 border border-red-200 rounded-xl transition cursor-pointer hover:bg-red-100">Clear</button>
                        <button @click="holdCurrentSale()" class="py-3 bg-amber-50 text-amber-600 border border-amber-200 rounded-xl transition cursor-pointer hover:bg-amber-100">Hold <span class="hidden sm:inline">(F6)</span></button>
                        <button @click="quickPay()" 
                                class="py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition flex items-center justify-center gap-1 cursor-pointer"
                                :disabled="cart.length === 0"
                                :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            <span>Quick <span class="hidden sm:inline">(F10)</span></span>
                        </button>
                        <button @click="openPaymentPopup()" 
                                class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition flex items-center justify-center gap-1 cursor-pointer"
                                :disabled="cart.length === 0"
                                :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            <span>Pay <span class="hidden sm:inline">(F4)</span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VARIANT SELECT POPUP MODAL -->
    <div x-show="showVariantModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 transform scale-100 transition duration-300"
             @click.away="showVariantModal = false">
            <div class="border-b border-slate-100 pb-3.5 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Select Variation</h3>
                    <p class="text-[10px] text-slate-400 truncate max-w-[260px]" x-text="selectedVariantProduct ? selectedVariantProduct.name : ''"></p>
                </div>
                <button @click="showVariantModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="space-y-2 max-h-80 overflow-y-auto">
                <template x-for="variant in (selectedVariantProduct ? selectedVariantProduct.variants : [])" :key="variant.id">
                    <button type="button"
                            @click="addVariantToCart(selectedVariantProduct, variant)"
                            class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="variant.stock_quantity <= 0">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-black text-slate-850" x-text="variant.label"></div>
                                <div class="text-[10px] text-slate-500 font-bold" x-text="'SKU: ' + (variant.sku || '-')"></div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs font-black text-blue-600">Rs. <span x-text="formatMoney(variant.retail_price)"></span></div>
                                <div class="text-[10px] font-bold" :class="variant.stock_quantity <= 0 ? 'text-red-600' : 'text-slate-500'" x-text="'Stock: ' + parseInt(variant.stock_quantity)"></div>
                            </div>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- EDIT CART ITEM POPUP MODAL -->
    <div x-show="showEditItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 transform scale-100 transition duration-300"
             @click.away="showEditItemModal = false">
            
            <div class="border-b border-slate-100 pb-3.5 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Edit Cart Item</h3>
                    <p class="text-[10px] text-slate-400 truncate max-w-[200px]" x-text="activeEditItem ? activeEditItem.name : ''"></p>
                </div>
                <button @click="showEditItemModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="space-y-4 text-xs font-bold">
                <!-- Unit Price -->
                <div>
                    <label class="block text-slate-500 mb-1">Unit Price (Rs.)</label>
                    <input type="number" x-model.number="editModalPrice" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs font-bold" />
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-slate-500 mb-1">Quantity (Stock: <span x-text="activeEditItem ? parseInt(activeEditItem.stock) : 0"></span>)</label>
                    <input type="number" x-model.number="editModalQty" 
                           :max="activeEditItem ? activeEditItem.stock : null"
                           min="1"
                           @input="if (activeEditItem && editModalQty > activeEditItem.stock) { editModalQty = activeEditItem.stock; }" 
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs font-bold" />
                </div>

                <!-- Discount Type & Value -->
                <div>
                    <label class="block text-slate-500 mb-1">Line Discount</label>
                    <div class="flex gap-2">
                        <!-- Discount Type Selector -->
                        <select x-model="editModalDiscountType" class="px-2 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none text-xs font-bold">
                            <option value="fixed">Fixed (Rs.)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                        <!-- Discount Value -->
                        <input type="number" x-model.number="editModalDiscountAmount" class="flex-1 px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs font-bold" />
                    </div>
                </div>

                <!-- Modal Subtotal preview -->
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-650">Line Total:</span>
                    <span class="text-blue-600 text-sm font-black">Rs. <span x-text="formatMoney(calculateEditModalLineTotal())"></span></span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                    <button @click="showEditItemModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 transition cursor-pointer">Cancel</button>
                    <button @click="saveEditItem()" class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition cursor-pointer">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYMENT / MIXED SPLIT POPUP MODAL (F4) -->
    <div x-show="showPaymentPopup" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="max-h-[calc(100dvh-1.5rem)] overflow-y-auto bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-200 transform scale-100 transition duration-300"
             @click.away="showPaymentPopup = false">
            
            <div class="border-b border-slate-100 pb-4 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">POS Checkout Split Payments</h3>
                    <p class="text-[10px] text-slate-400">Complete sale using single or split payment methods</p>
                </div>
                <button @click="showPaymentPopup = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Grand Total Alert -->
            <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between text-xs font-bold">
                <span class="text-slate-600">Total Payable:</span>
                <span class="text-blue-600 text-lg font-black">Rs. <span x-text="formatMoney(calculateGrandTotal())"></span></span>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between"><label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Payment Allocations</label><button @click="addPaymentRow()" class="text-xs font-bold text-blue-600">+ Add Payment</button></div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2"><template x-for="method in ['Cash','Bank Transfer','Cheque','Customer Credit / Due']"><button @click="addPaymentRow(method)" class="min-h-11 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-[10px] font-bold hover:border-blue-400" x-text="method === 'Customer Credit / Due' ? 'Due' : method"></button></template></div>
                <div class="max-h-80 space-y-2 overflow-y-auto pr-1"><template x-for="(row,index) in paymentRows" :key="row.id"><div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_130px_44px] gap-2">
                        <select x-model="row.method" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Other">Other</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Customer Credit / Due">Customer Credit / Due</option>
                        </select>
                        <input type="number" min="0.01" step="0.01" x-model.number="row.amount" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-right text-xs font-bold"><button @click="removePaymentRow(index)" class="min-h-11 rounded-lg border border-red-200 bg-red-50 text-xl text-red-500" aria-label="Remove payment">×</button></div>
                    <input x-show="!['Cash','Customer Credit / Due','Cheque'].includes(row.method)" x-model="row.reference_no" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs" placeholder="Reference number / account">
                    <div x-show="row.method === 'Cheque'" class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border border-amber-200 bg-amber-50 p-2"><input x-model="row.cheque.cheque_number" class="rounded border border-amber-300 px-2 py-2 text-xs" placeholder="Cheque number *"><input type="date" x-model="row.cheque.cheque_date" class="rounded border border-amber-300 px-2 py-2 text-xs"><input x-model="row.cheque.bank_name" class="rounded border border-amber-300 px-2 py-2 text-xs" placeholder="Bank *"><input x-model="row.cheque.branch_name" class="rounded border border-amber-300 px-2 py-2 text-xs" placeholder="Branch"><input x-model="row.cheque.account_holder" class="rounded border border-amber-300 px-2 py-2 text-xs sm:col-span-2" placeholder="Account holder *"></div>
                </div></template></div>
            </div>

            <!-- Total Paid, Balance and Change calculation summaries -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 text-xs font-bold mt-5 pt-4 border-t border-slate-100">
                <div class="bg-slate-50 border border-slate-200/50 rounded-xl p-2.5 text-center">
                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider mb-1">Total Paid</span>
                    <span class="text-xs font-black text-slate-800">Rs. <span x-text="formatMoney(calculateSplitTotalPaid())"></span></span>
                </div>
                <div class="bg-slate-50 border border-slate-200/50 rounded-xl p-2.5 text-center">
                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider mb-1">Change Due</span>
                    <span class="text-xs font-black text-emerald-600">Rs. <span x-text="formatMoney(calculateSplitChange())"></span></span>
                </div>
                <div class="bg-slate-50 border border-slate-200/50 rounded-xl p-2.5 text-center"
                     :class="calculateSplitRemainingDue() > 0 ? 'bg-red-50/50 border-red-100' : ''">
                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider mb-1">Due Amount</span>
                    <span class="text-xs font-black text-red-600">Rs. <span x-text="formatMoney(calculateSplitRemainingDue())"></span></span>
                </div>
            </div>

            <!-- Submit details button -->
            <div class="grid grid-cols-2 gap-3 text-xs font-bold mt-6">
                <button @click="showPaymentPopup = false" class="py-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 transition cursor-pointer">Cancel</button>
                <button @click="submitPOSCheckout()" 
                        class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/20 transition cursor-pointer flex items-center justify-center gap-1.5"
                        :disabled="!paymentsValid()"
                        :class="!paymentsValid() ? 'opacity-50 cursor-not-allowed' : ''">
                    <span>Complete Sale <span class="hidden sm:inline">(F8)</span></span>
                </button>
            </div>
        </div>
    </div>

    <!-- HOLD SALES QUEUE POPUP MODAL (F6) -->
    <div x-show="showHoldSalesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 flex flex-col justify-between"
             @click.away="showHoldSalesModal = false">
            
            <div class="border-b border-slate-100 pb-4 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Hold Invoices Queue</h3>
                    <p class="text-[10px] text-slate-400">Recall transactions back into the active checkout cart</p>
                </div>
                <button @click="showHoldSalesModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Hold sales list -->
            <div class="space-y-3 flex-1 overflow-y-auto max-h-[360px] min-h-60 pr-1">
                <template x-for="hold in holdSales" :key="hold.id">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/60 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wide">Held</span>
                                <span>Ref: <span x-text="hold.reference_no"></span></span>
                            </div>
                            <p class="text-[10px] text-slate-500 font-semibold">
                                <span x-text="hold.customer_name"></span> &bull; <span x-text="hold.hold_date"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-black text-slate-800">Rs. <span x-text="formatMoney(hold.total_amount)"></span></span>
                            <button @click="resumeHoldFromQueue(hold)" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer">Recall</button>
                        </div>
                    </div>
                </template>
                <template x-if="holdSales.length === 0">
                    <div class="py-12 text-center text-slate-400 font-semibold text-xs flex flex-col items-center justify-center gap-2">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>No transactions currently on hold.</span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- QUICK ADD CUSTOMER MODAL -->
    <div x-show="showAddCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200"
             @click.away="showAddCustomerModal = false">
            
            <div class="border-b border-slate-100 pb-3.5 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Add Retail Customer</h3>
                    <p class="text-[10px] text-slate-400">Quick-create customer details</p>
                </div>
                <button @click="showAddCustomerModal = false" class="text-slate-400 hover:text-white cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Inputs -->
            <div class="space-y-4 text-xs font-bold">
                <div>
                    <label class="block text-slate-500 mb-1">Customer Name *</label>
                    <input type="text" x-model="newCustName" required placeholder="e.g. John Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none" />
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Mobile / Phone</label>
                    <input type="text" x-model="newCustPhone" placeholder="e.g. 0771234567" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none" />
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Email Address</label>
                    <input type="email" x-model="newCustEmail" placeholder="e.g. john@example.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-250 rounded-xl focus:outline-none" />
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                    <button @click="showAddCustomerModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 transition cursor-pointer">Cancel</button>
                    <button @click="submitQuickCustomer()" class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition cursor-pointer">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SUCCESS INVOICE PREVIEW / RECEIPT SCREEN (58mm/80mm layout) -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 flex flex-col justify-between"
             @click.away="showSuccessModal = false">
            
            <div class="text-center pb-4 border-b border-slate-100">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3 class="text-base font-black text-slate-800 leading-none">Checkout Successful</h3>
                <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-wider">Invoice registered successfully</p>
            </div>

            <!-- Receipt layout inside modal -->
            <div class="my-4 bg-slate-50 border border-slate-200/50 rounded-2xl p-4 font-mono text-[10px] text-slate-700 space-y-3 max-h-[300px] overflow-y-auto leading-relaxed" id="receipt-print-area">
                <div class="text-center">
                    <h4 class="font-extrabold text-xs text-slate-900 uppercase">Phone Shop POS</h4>
                    <p class="text-[9px] text-slate-500 leading-tight">Retail POS & Accessories System</p>
                    <p class="text-[8px] text-slate-500">Colombo, Sri Lanka &bull; +94 76 247 2822</p>
                </div>
                
                <div class="border-t border-dashed border-slate-300 pt-2 space-y-0.5">
                    <p><strong>Invoice:</strong> <span x-text="successData ? successData.invoice_no : ''"></span></p>
                    <p><strong>Date:</strong> <span x-text="successData ? successData.date : ''"></span></p>
                    <p><strong>Cashier:</strong> {{ auth()->user()->name }}</p>
                    <p><strong>Customer:</strong> <span x-text="successData ? successData.customer : ''"></span></p>
                </div>

                <table class="w-full border-t border-dashed border-slate-300 pt-2 text-left">
                    <thead>
                        <tr class="font-bold border-b border-slate-200 pb-1">
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in (successData ? successData.items : [])">
                            <tr class="border-b border-slate-100/50">
                                <td class="py-1">
                                    <span x-text="item.name"></span>
                                    <template x-if="item.serial_no">
                                        <span class="block text-[8px] text-slate-500 font-bold" x-text="'IMEI: ' + item.serial_no"></span>
                                    </template>
                                </td>
                                <td class="text-center py-1" x-text="item.quantity"></td>
                                <td class="text-right py-1" x-text="'Rs. ' + formatMoney(item.line_total)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="border-t border-dashed border-slate-300 pt-2 space-y-1 text-right">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span x-text="'Rs. ' + formatMoney(successData ? successData.subtotal : 0)"></span>
                    </div>
                    <div class="flex justify-between text-red-650 font-semibold" x-show="successData && successData.discount > 0">
                        <span>Discount:</span>
                        <span x-text="'-Rs. ' + formatMoney(successData ? successData.discount : 0)"></span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1">
                        <span>Total Payable:</span>
                        <span x-text="'Rs. ' + formatMoney(successData ? successData.payable : 0)"></span>
                    </div>
                    
                    <template x-if="successData && successData.payment_rows && successData.payment_rows.length > 0">
                        <div class="border-t border-dashed border-slate-300 mt-1.5 pt-1.5 space-y-1">
                            <template x-for="(row, index) in successData.payment_rows" :key="index">
                                <div>
                                    <div class="flex justify-between font-semibold">
                                        <span x-text="row.method === 'Customer Credit / Due' ? 'Balance Due:' : (row.method === 'Cheque' ? 'Cheque Pending:' : 'Paid (' + row.method + '):')"></span>
                                        <span x-text="'Rs. ' + formatMoney(row.amount)"></span>
                                    </div>
                                    <template x-if="row.method === 'Cheque'">
                                        <div class="text-[9px] text-slate-500 text-left mt-0.5 leading-tight">
                                            <span x-text="'Chq No: ' + (row.cheque?.cheque_number || 'N/A')"></span><br>
                                            <span x-text="'Date: ' + (row.cheque?.cheque_date || 'N/A')"></span><br>
                                            <span x-text="'Bank: ' + (row.cheque?.bank_name || 'N/A')"></span>
                                        </div>
                                    </template>
                                    <template x-if="row.method === 'Bank Transfer' && row.reference_number">
                                        <div class="text-[9px] text-slate-500 text-left mt-0.5">
                                            <span x-text="'Ref: ' + row.reference_number"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!(successData && successData.payment_rows && successData.payment_rows.length > 0)">
                        <div class="flex justify-between font-semibold mt-1.5 pt-1.5 border-t border-dashed border-slate-300">
                            <span>Paid Total:</span>
                            <span x-text="'Rs. ' + formatMoney(successData ? successData.paid : 0)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-emerald-600 font-bold">
                        <span>Change Cash:</span>
                        <span x-text="'Rs. ' + formatMoney(successData ? successData.change : 0)"></span>
                    </div>
                    <template x-if="successData && successData.points_earned > 0">
                        <div class="text-center text-blue-600 font-black text-[9px] border-t border-dashed border-slate-300 pt-1.5 mt-1">
                            LOYALTY POINTS EARNED: <span x-text="successData.points_earned"></span> PTS!
                        </div>
                    </template>
                </div>

                <div class="text-center border-t border-dashed border-slate-300 pt-2 mt-2 text-[8px] text-slate-400">
                    <p>Thank you for shopping with us!</p>
                    <p class="font-extrabold uppercase mt-1 text-[7px] tracking-wider">Powered by {{ config('app.developer.name', 'twinsofte.com') }}</p>
                </div>
            </div>

            <!-- Print Actions -->
            <div class="space-y-2.5">
                <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                    <button @click="printA4Invoice(successData)" class="py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition flex items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2-9v10H8V9"/></svg>
                        <span>Print A4 Invoice</span>
                    </button>
                    <button @click="showSuccessModal = false" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer">
                        Close Preview
                    </button>
                </div>

                <!-- Sharing and WhatsApp integrations -->
                <div class="flex items-center gap-2">
                    <a :href="'https://api.whatsapp.com/send?phone=' + (getSelectedCustomer() && getSelectedCustomer().phone !== 'N/A' ? getSelectedCustomer().phone : '') + '&text=Thank%20you%20for%20your%20purchase!%20Invoice%20No:%20' + (successData ? successData.invoice_no : '')" 
                       target="_blank" 
                       class="flex-1 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-center text-xs font-bold flex items-center justify-center gap-1 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.18 1.457 4.8 1.458 5.432 0 9.854-4.41 9.856-9.83.001-2.628-1.01-5.1-2.855-6.947a9.785 9.785 0 0 0-6.98-2.87c-5.438 0-9.86 4.41-9.862 9.83-.001 1.93.488 3.81 1.419 5.46l-.999 3.648 3.766-.988z"/></svg>
                        <span>Share on WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- UI SCRIPTS -->
    <script>
        function retailPosApp() {
            return {
                categories: @json($categories),
                products: @json($products),
                customers: @json($customers),
                paymentMethods: @json($paymentMethods),
                holdSales: @json($holdSales),
                resumeHold: @json($resumeHold),
                editSale: @json($editSale ?? null),
                settings: @json($posSettings),
                
                // Selectors
                sourceType: '{{ $sourceType ?? 'store' }}',
                lorryId: '{{ $activeLorryId ?? '' }}',
                tripId: '{{ $activeTripId ?? '' }}',
                selectedCategory: 'all',
                searchQuery: '',
                selectedCustomerId: 1, // Walk-in
                currentHoldId: null,
                mobileTab: 'products', // products, cart

                // Totals & Cart
                cart: [],
                totals: { subTotal: 0, discount: 0, addedTax: 0, includedTax: 0, totalTax: 0, grandTotal: 0 },
                globalDiscountType: 'fixed',
                globalDiscountValue: 0,
                taxPercentage: Number(@json($posSettings['taxPercentage'] ?? 15)),
                taxMode: @json($posSettings['taxMode'] ?? 'exclusive'),
                shippingCost: 0,
                currencySymbol: @json($posSettings['currencySymbol'] ?? 'Rs.'),
                decimalPlaces: Number(@json($posSettings['decimalPlaces'] ?? 2)),
                enableTax: Boolean(@json($posSettings['enableTax'] ?? true)),
                enableDiscount: Boolean(@json($posSettings['enableDiscount'] ?? true)),

                // Helpers
                toNumber(value) {
                    if (value === null || value === undefined || value === '') return 0;
                    if (typeof value === 'number') return value;
                    if (typeof value === 'string') {
                        let cleaned = value.replace(/[^\d.-]/g, '');
                        let parsed = parseFloat(cleaned);
                        return isNaN(parsed) ? 0 : parsed;
                    }
                    return 0;
                },
                roundMoney(value) {
                    return Math.round((this.toNumber(value) + Number.EPSILON) * 100) / 100;
                },

                // Mixed split payments
                payments: {
                    'Cash': 0,
                    'Bank Transfer': 0
                    ,'Cheque': 0
                },
                paymentReferences: {
                    'Cash': '',
                    'Bank Transfer': ''
                },
                chequeDetails: { cheque_number: '', cheque_date: '', bank_name: '', branch_name: '', account_holder_name: '', account_number: '', reference_number: '' },
                paymentRows: [],

                // Clock and Modals
                liveTime: '',
                showMenuDrawer: false,
                showHoldSalesModal: false,
                showAddCustomerModal: false,
                showPaymentPopup: false,
                showSuccessModal: false,
                successData: null,

                // Edit Item Modal State
                showEditItemModal: false,
                showVariantModal: false,
                selectedVariantProduct: null,
                activeEditItem: null,
                activeEditIndex: null,
                editModalPrice: 0,
                editModalQty: 1,
                editModalDiscountType: 'fixed',
                editModalDiscountAmount: 0,

                // Quick Customer Add
                newCustName: '',
                newCustPhone: '',
                newCustEmail: '',

                init() {
                    // Start clock
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);

                    // Load local storage hold sales queue
                    const storedHolds = localStorage.getItem('retail_held_sales');
                    if (storedHolds) {
                        this.holdSales = JSON.parse(storedHolds);
                    }

                    if (this.resumeHold) {
                        this.recallHeldSale(this.resumeHold, true);
                    } else if (this.editSale) {
                        this.recallHeldSale(this.editSale, false);
                    }

                    // Focus search field
                    this.$nextTick(() => {
                        document.getElementById('search-input')?.focus();
                    });

                    // Keyboard shortcuts binding
                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'F1') {
                            e.preventDefault();
                            document.getElementById('search-input')?.focus();
                        } else if (e.key === 'F2') {
                            e.preventDefault();
                            this.showAddCustomerModal = true;
                        } else if (e.key === 'F4') {
                            e.preventDefault();
                            if (this.cart.length > 0) {
                                this.openPaymentPopup();
                            }
                        } else if (e.key === 'F6') {
                            e.preventDefault();
                            this.showHoldSalesModal = true;
                        } else if (e.key === 'F8') {
                            e.preventDefault();
                            document.getElementById('search-input').focus();
                        } else if (e.key === 'F10') {
                            e.preventDefault();
                            this.quickPay();
                        } else if (e.key === 'F12') {
                            e.preventDefault();
                            if (this.showPaymentPopup && this.paymentsValid()) {
                                this.submitPOSCheckout();
                            }
                        } else if (e.key === 'Escape') {
                            if (this.showPaymentPopup) {
                                this.showPaymentPopup = false;
                            } else if (this.showSuccessModal) {
                                this.startNewSale();
                            } else if (this.showHoldSalesModal) {
                                this.showHoldSalesModal = false;
                            } else if (this.showAddCustomerModal) {
                                this.showAddCustomerModal = false;
                            } else if (this.showVariantModal) {
                                this.showVariantModal = false;
                            }
                        } else if (e.key === 'Delete') {
                            // Delete last element in cart
                            if (this.cart.length > 0) {
                                this.cart.pop();
                                this.paidAmount = this.calculateGrandTotal();
                            }
                        }
                    });
                },

                updateTime() {
                    const now = new Date();
                    const date = now.toLocaleDateString('en-GB');
                    const time = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                    });
                    this.liveTime = `${date} ${time}`;
                },

                // Scan detection logic
                watchSearchQuery(value) {
                    if (!value) return;
                    
                    // Look for exact barcode/SKU match
                    let match = this.products.find(p => p.barcode === value || p.sku === value);
                    if (match) {
                        this.playBeep();
                        this.addToCart(match);
                        this.searchQuery = '';
                    }
                },

                playBeep() {
                    try {
                        let context = new (window.AudioContext || window.webkitAudioContext)();
                        let oscillator = context.createOscillator();
                        let gain = context.createGain();
                        oscillator.type = 'sine';
                        oscillator.frequency.value = 800; // 800Hz beep frequency
                        gain.gain.setValueAtTime(0.08, context.currentTime); 
                        gain.gain.exponentialRampToValueAtTime(0.005, context.currentTime + 0.12); // fade out
                        oscillator.connect(gain);
                        gain.connect(context.destination);
                        oscillator.start();
                        oscillator.stop(context.currentTime + 0.12);
                    } catch (e) {
                        console.log("Audio beep blocked: " + e.message);
                    }
                },

                // Filters product list on left
                filteredProducts() {
                    let query = this.searchQuery.toLowerCase().trim();
                    return this.products.filter(p => {
                        // Category filter
                        if (this.selectedCategory !== 'all' && p.category_slug !== this.selectedCategory) {
                            return false;
                        }
                        
                        // Query match (checks name, barcode, SKU, or active IMEIs)
                        if (query) {
                            let matchName = p.name.toLowerCase().includes(query);
                            let matchSku = p.sku.toLowerCase().includes(query);
                            let matchBarcode = p.barcode ? p.barcode.toLowerCase().includes(query) : false;
                            let matchIMEI = p.serials ? p.serials.some(sn => sn.toLowerCase().includes(query)) : false;
                            return matchName || matchSku || matchBarcode || matchIMEI;
                        }
                        
                        return true;
                    });
                },

                // Add item directly to checkout cart (Retail workflow)
                addToCart(product) {
                    if (product.stock <= 0) return;
                    if (product.variants && product.variants.length > 0) {
                        this.selectedVariantProduct = product;
                        this.showVariantModal = true;
                        return;
                    }

                    this.playBeep();

                    // Accessories can aggregate quantities
                    if (product.category_slug !== 'smartphones') {
                        let existing = this.cart.find(item => item.product_id === product.id && !item.variant_id);
                        if (existing) {
                            if (existing.quantity < product.stock) {
                                existing.quantity++;
                                this.updateLineTotal(existing);
                            } else {
                                Swal.fire({ text: 'Cannot add more than available stock quantity!', icon: 'warning' });
                            }
                            return;
                        }
                    }

                    // Mobile phones require separate cart items to track unique IMEIs
                    let serialNo = product.serials && product.serials.length > 0 ? product.serials[0] : '';
                    if (product.category_slug === 'smartphones' && serialNo) {
                        let isDuplicate = this.cart.some(item => item.product_id === product.id && item.serial_no === serialNo);
                        if (isDuplicate) {
                            Swal.fire({ text: 'This specific IMEI is already in the cart!', icon: 'warning' });
                            return;
                        }
                    }

                    this.cart.push({
                        product_id: product.id,
                        name: product.name,
                        quantity: 1,
                        cost_price: this.toNumber(product.cost_price),
                        selling_price: this.toNumber(product.retail_price),
                        discount_amount: 0,
                        discount_type: 'fixed',
                        tax_mode: product.tax_mode,
                        tax_status: product.tax_status,
                        tax_rate: this.toNumber(product.tax_rate) || 0,
                        tax_id: product.tax_id || null,
                        line_total: this.toNumber(product.retail_price),
                        note: '',
                        stock: this.toNumber(product.stock),
                        is_smartphone: product.category_slug === 'smartphones',
                        serials: product.serials || [],
                        serial_no: serialNo
                    });

                    this.recalculateCartTotals();
                },

                addVariantToCart(product, variant) {
                    if (variant.stock_quantity <= 0) return;

                    this.playBeep();
                    const existing = this.cart.find(item => item.product_id === product.id && item.variant_id === variant.id);
                    if (existing) {
                        if (existing.quantity < variant.stock_quantity) {
                            existing.quantity++;
                            this.updateLineTotal(existing);
                        } else {
                            Swal.fire({ text: 'Cannot add more than available stock quantity!', icon: 'warning' });
                        }
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            variant_id: variant.id,
                            variant_label: variant.label,
                            name: product.name,
                            quantity: 1,
                            cost_price: variant.cost_price || product.cost_price,
                            selling_price: variant.retail_price || product.retail_price,
                            discount_amount: 0,
                            discount_type: 'fixed',
                            tax_rate: product.tax_rate || 0,
                            tax_id: product.tax_id || null,
                            line_total: variant.retail_price || product.retail_price,
                            note: '',
                            stock: variant.stock_quantity,
                            is_smartphone: product.category_slug === 'smartphones',
                            serials: [],
                            serial_no: ''
                        });
                    }

                    this.showVariantModal = false;
                    this.selectedVariantProduct = null;
                    this.recalculateCartTotals();
                },

                openEditItemModal(item, index) {
                    this.activeEditItem = item;
                    this.activeEditIndex = index;
                    this.editModalPrice = item.selling_price;
                    this.editModalQty = item.quantity;
                    this.editModalDiscountType = item.discount_type || 'fixed';
                    this.editModalDiscountAmount = item.discount_amount || 0;
                    this.showEditItemModal = true;
                },

                calculateEditModalLineTotal() {
                    let baseTotal = this.editModalPrice * this.editModalQty;
                    if (this.editModalDiscountType === 'percentage') {
                        return baseTotal - (baseTotal * (this.editModalDiscountAmount / 100));
                    } else {
                        return Math.max(0, baseTotal - this.editModalDiscountAmount);
                    }
                },

                saveEditItem() {
                    if (this.editModalQty > this.activeEditItem.stock) {
                        Swal.fire({ text: 'Cannot add more than available stock quantity (' + parseInt(this.activeEditItem.stock) + ')!', icon: 'warning' });
                        return;
                    }
                    if (this.editModalQty < 1 || isNaN(this.editModalQty)) {
                        Swal.fire({ text: 'Quantity must be at least 1!', icon: 'warning' });
                        return;
                    }
                    
                    let item = this.cart[this.activeEditIndex];
                    item.selling_price = this.editModalPrice;
                    item.quantity = this.editModalQty;
                    item.discount_type = this.editModalDiscountType;
                    item.discount_amount = this.editModalDiscountAmount;
                    
                    this.updateLineTotal(item);
                    this.showEditItemModal = false;
                },

                // Restrict steppers
                incrementQty(item) {
                    if (item.quantity < item.stock) {
                        item.quantity++;
                        this.updateLineTotal(item);
                    } else {
                        Swal.fire({ text: 'Cannot add more than available stock quantity (' + parseInt(item.stock) + ')!', icon: 'warning' });
                    }
                },

                decrementQty(item) {
                    if (item.quantity > 1) {
                        item.quantity--;
                        this.updateLineTotal(item);
                    }
                },

                // Manual input constraint clamp
                clampCartQuantity(item) {
                    if (item.quantity > item.stock) {
                        Swal.fire({ text: 'Clamped quantity to maximum available stock (' + parseInt(item.stock) + ')!', icon: 'info' });
                        item.quantity = item.stock;
                    }
                    if (item.quantity < 1 || isNaN(item.quantity)) {
                        item.quantity = 1;
                    }
                    this.recalculateCartTotals();
                },

                updateLineTotal(item) {
                    this.recalculateCartTotals();
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.recalculateCartTotals();
                },

                clearCart() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Clear the current transaction cart?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#3b82f6',
                        confirmButtonText: 'Yes, clear it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.cart = [];
                            this.globalDiscountType = 'fixed';
                            this.globalDiscountValue = 0;
                            this.shippingCost = 0;
                            this.recalculateCartTotals();
                        }
                    });
                },

                // Calculations
                recalculateCartTotals() {
                    let subTotal = 0;
                    let includedTax = 0;
                    let addedTax = 0;
                    let totalTax = 0;
                    
                    let globalDiscount = this.globalDiscountType === 'percentage'
                        ? 0 
                        : this.toNumber(this.globalDiscountValue);

                    // First pass: Calculate item raw line totals
                    this.cart.forEach(item => {
                        let price = this.toNumber(item.selling_price);
                        let qty = this.toNumber(item.quantity);
                        let discount = this.toNumber(item.discount_amount);
                        let baseTotal = price * qty;
                        
                        if (item.discount_type === 'percentage') {
                            item.line_total = this.roundMoney(baseTotal - (baseTotal * (discount / 100)));
                        } else {
                            item.line_total = Math.max(0, this.roundMoney(baseTotal - discount));
                        }
                        
                        subTotal += item.line_total;
                    });
                    
                    // Global Discount Percentage
                    if (this.globalDiscountType === 'percentage') {
                        globalDiscount = (subTotal * this.toNumber(this.globalDiscountValue)) / 100;
                    }
                    globalDiscount = this.roundMoney(globalDiscount);

                    // Second pass: Tax calculation
                    if (this.enableTax) {
                        this.cart.forEach(item => {
                            let itemShare = subTotal > 0 ? (item.line_total / subTotal) : 0;
                            let itemDiscountShare = this.roundMoney(globalDiscount * itemShare);
                            let discountedAmount = Math.max(0, item.line_total - itemDiscountShare);
                            
                            let taxStatus = item.tax_status || 'taxable';
                            let rate = (['non_taxable', 'zero_rated', 'exempt'].includes(taxStatus)) ? 0 : this.toNumber(item.tax_rate !== undefined ? item.tax_rate : this.taxPercentage);
                            
                            let itemTaxMode = item.tax_mode || 'use_global';
                            if (itemTaxMode === 'use_global') itemTaxMode = this.taxMode || 'exclusive';
                            
                            let itemTax = 0;
                            if (itemTaxMode === 'inclusive') {
                                let taxable = this.roundMoney(discountedAmount / (1 + (rate / 100)));
                                itemTax = this.roundMoney(discountedAmount - taxable);
                                item.taxable_amount = taxable;
                                item.tax_amount = itemTax;
                                includedTax += itemTax;
                            } else {
                                itemTax = this.roundMoney(discountedAmount * (rate / 100));
                                item.taxable_amount = discountedAmount;
                                item.tax_amount = itemTax;
                                addedTax += itemTax;
                            }
                            totalTax += itemTax;
                        });
                    }
                    
                    this.totals.subTotal = this.roundMoney(subTotal);
                    this.totals.discount = globalDiscount;
                    this.totals.addedTax = this.roundMoney(addedTax);
                    this.totals.includedTax = this.roundMoney(includedTax);
                    this.totals.totalTax = this.roundMoney(totalTax);
                    this.totals.grandTotal = Math.max(0, this.roundMoney(subTotal - globalDiscount + addedTax + this.toNumber(this.shippingCost)));
                    
                    this.paidAmount = this.totals.grandTotal;
                },

                calculateSubtotal() { return this.totals.subTotal; },
                calculateDiscountAmount() { return this.totals.discount; },
                calculateTaxDetails() { return { totalTax: this.totals.totalTax, addedTax: this.totals.addedTax, includedTax: this.totals.includedTax }; },
                calculateTaxAmount() { return this.totals.totalTax; },
                calculateGrandTotal() { return this.totals.grandTotal; },

                // Payment popup methods
                openPaymentPopup() {
                    this.paymentRows = [];
                    this.addPaymentRow('Cash');
                    this.showPaymentPopup = true;
                },

                addPaymentRow(method = 'Cash') {
                    const amount = Math.max(0, this.calculateGrandTotal() - this.calculateSplitTotalPaid());
                    this.paymentRows.push({ id: Date.now() + Math.random(), method, amount, reference_no: '', cheque: { cheque_number: '', cheque_date: '', bank_name: '', branch_name: '', account_holder: '', account_number: '', notes: '' } });
                },
                removePaymentRow(index) { this.paymentRows.splice(index, 1); },

                calculateSplitTotalPaid() {
                    return this.paymentRows.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
                },

                calculateSplitChange() {
                    let totalPaid = this.calculateSplitTotalPaid();
                    let payable = this.calculateGrandTotal();
                    return totalPaid > payable ? totalPaid - payable : 0;
                },

                calculateSplitRemainingDue() {
                    let totalPaid = this.calculateSplitTotalPaid();
                    let payable = this.calculateGrandTotal();
                    return payable > totalPaid ? payable - totalPaid : 0;
                },
                paymentsValid() {
                    if (!this.paymentRows.length || Math.abs(this.calculateSplitRemainingDue()) > .009 || this.calculateSplitChange() > .009) return false;
                    return this.paymentRows.every(row => Number(row.amount) > 0 && (row.method !== 'Cheque' || (row.cheque.cheque_number && row.cheque.cheque_date && row.cheque.bank_name && row.cheque.account_holder)));
                },

                // Save Customer
                getSelectedCustomer() {
                    return this.customers.find(c => c.id == this.selectedCustomerId);
                },

                submitQuickCustomer() {
                    if (!this.newCustName) return;
                    
                    fetch('{{ route("pos.retail.customer") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: this.newCustName,
                            phone: this.newCustPhone,
                            email: this.newCustEmail
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.customers.push(data.customer);
                            this.selectedCustomerId = data.customer.id;
                            this.showAddCustomerModal = false;
                            this.newCustName = '';
                            this.newCustPhone = '';
                            this.newCustEmail = '';
                        }
                    });
                },

                // Hold Cart System (Persisted in localStorage)
                holdCurrentSale() {
                    if (this.cart.length === 0) {
                        Swal.fire({ text: 'No products in cart to hold.', icon: 'warning' });
                        return;
                    }

                    const holdRef = 'HOLD-' + Math.floor(1000 + Math.random() * 9000);
                    const now = new Date();
                    const newHold = {
                        id: Date.now(),
                        reference_no: holdRef,
                        customer_id: this.selectedCustomerId,
                        customer_name: this.getSelectedCustomer().name,
                        hold_date: now.toLocaleString(),
                        total_amount: this.calculateGrandTotal(),
                        subtotal: this.calculateSubtotal(),
                        discountType: this.globalDiscountType,
                        discountValue: this.globalDiscountValue,
                        tax: this.calculateTaxAmount(),
                        taxPercentage: this.taxPercentage,
                        items: this.cart,
                        shippingCost: this.shippingCost
                    };

                    this.holdSales.push(newHold);
                    localStorage.setItem('retail_held_sales', JSON.stringify(this.holdSales));
                    
                    Swal.fire({ text: 'Sale placed on hold with Reference: ' + holdRef, icon: 'success' });
                    this.cart = [];
                    this.globalDiscountType = 'fixed';
                    this.globalDiscountValue = 0;
                    this.shippingCost = 0;
                },

                recallHeldSale(hold, fromServer = false) {
                    this.cart = hold.items;
                    this.selectedCustomerId = hold.customer_id;
                    this.currentHoldId = hold.id;
                    this.globalDiscountType = hold.discountType || 'fixed';
                    this.globalDiscountValue = hold.discountValue || 0;
                    this.taxPercentage = hold.taxPercentage;
                    this.shippingCost = hold.shippingCost;
                    this.currentHoldId = fromServer ? hold.id : null;

                    // Remove from list
                    this.holdSales = this.holdSales.filter(h => h.id !== hold.id);
                    if (!fromServer) {
                        localStorage.setItem('retail_held_sales', JSON.stringify(this.holdSales));
                    }

                    this.showHoldSalesModal = false;
                },

                resumeHoldFromQueue(hold) {
                    if (hold.items) {
                        this.recallHeldSale(hold);
                        return;
                    }

                    window.location.href = '{{ route("pos.retail") }}?hold_id=' + hold.id;
                },

                quickPay() {
                    if (this.cart.length === 0) {
                        Swal.fire({ text: 'Please add products to the cart before checking out.', icon: 'warning' });
                        return;
                    }
                    this.paymentRows = [{
                        id: Date.now(),
                        method: 'Cash',
                        amount: this.calculateGrandTotal(),
                        reference_no: '',
                        cheque: { cheque_number: '', cheque_date: '', bank_name: '', branch_name: '', account_holder: '', account_number: '', notes: '' }
                    }];
                    this.submitPOSCheckout();
                },

                printDueInvoice() {
                    if (this.cart.length === 0) {
                        Swal.fire({ text: 'Please add products to the cart before creating a due invoice.', icon: 'warning' });
                        return;
                    }

                    const customer = this.getSelectedCustomer();
                    if (!customer || customer.name.toLowerCase().includes('walk-in')) {
                        const customerOptions = {};
                        this.customers.forEach(c => {
                            if (!c.name.toLowerCase().includes('walk-in')) {
                                customerOptions[c.id] = c.name + (c.phone ? ' - ' + c.phone : '');
                            }
                        });

                        Swal.fire({
                            title: 'Customer Required',
                            text: 'Select a registered customer so this due invoice can be tracked until payment is completed.',
                            icon: 'warning',
                            input: 'select',
                            inputOptions: customerOptions,
                            inputPlaceholder: 'Select a customer',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Select & Continue',
                            denyButtonText: 'Add New',
                            confirmButtonColor: '#d97706',
                            denyButtonColor: '#10b981',
                        }).then(result => {
                            if (result.isConfirmed && result.value) {
                                this.selectedCustomerId = result.value;
                                this.printDueInvoice();
                            } else if (result.isDenied) {
                                this.showPaymentPopup = false;
                                setTimeout(() => { this.showAddCustomerModal = true; }, 300);
                            }
                        });
                        return;
                    }

                    const printWindow = window.open('about:blank', '_blank');
                    this.submitPOSCheckout({ duePrint: true, printWindow });
                },

                // Submits checkout details to Laravel backend controller
                submitPOSCheckout(options = {}) {
                    if (this.cart.length === 0) return;

                    const payable = this.calculateGrandTotal();
                    const checkoutRows = options.duePrint
                        ? [{
                            id: Date.now(),
                            method: 'Customer Credit / Due',
                            amount: payable,
                            reference_no: '',
                            cheque: { cheque_number: '', cheque_date: '', bank_name: '', branch_name: '', account_holder: '', account_number: '', notes: '' }
                        }]
                        : this.paymentRows;
                    let hasCheque = checkoutRows.some(row => row.method === 'Cheque');
                    let customer = this.customers.find(c => c.id == this.selectedCustomerId);
                    if ((hasCheque || options.duePrint) && (!customer || customer.name.toLowerCase().includes('walk-in'))) {
                        let customerOptions = {};
                        this.customers.forEach(c => {
                            if (!c.name.toLowerCase().includes('walk-in')) {
                                customerOptions[c.id] = c.name + (c.phone ? ' - ' + c.phone : '');
                            }
                        });
                        
                        Swal.fire({
                            title: 'Customer Required',
                            text: options.duePrint
                                ? 'Please select a registered customer to create a due invoice.'
                                : 'Please select a valid customer (not Walk-in) to process a Cheque payment.',
                            icon: 'warning',
                            input: 'select',
                            inputOptions: customerOptions,
                            inputPlaceholder: 'Select a customer',
                            showCancelButton: true,
                            confirmButtonText: 'Select & Continue',
                            showDenyButton: true,
                            denyButtonText: 'Add New',
                            confirmButtonColor: '#3b82f6',
                            denyButtonColor: '#10b981',
                        }).then((result) => {
                            if (result.isConfirmed && result.value) {
                                this.selectedCustomerId = result.value;
                                this.submitPOSCheckout(options);
                            } else if (result.isDenied) {
                                this.showPaymentPopup = false;
                                setTimeout(() => { this.showAddCustomerModal = true; }, 300);
                            }
                        });
                        return;
                    }

                    let totalPaid = options.duePrint ? 0 : this.calculateSplitTotalPaid();

                    fetch('{{ route("pos.retail.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            customer_id: this.selectedCustomerId,
                            items: this.cart,
                            total_amount: this.calculateSubtotal(),
                            discount_type: this.globalDiscountType,
                            discount_amount: this.calculateDiscountAmount(),
                            tax_amount: this.calculateTaxAmount(),
                            payable_amount: payable,
                            payment_rows: checkoutRows,
                            paid_amount: totalPaid,
                            checkout_mode: options.duePrint ? 'due_print' : 'standard',
                            hold_id: this.currentHoldId,
                            store_id: {{ $activeStoreId ?? 1 }},
                            source_type: this.sourceType,
                            lorry_id: this.lorryId,
                            trip_id: this.tripId,
                            edit_sale_id: this.editSale?.id
                        })
                    })
                    .then(async res => {
                        const contentType = res.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return res.json();
                        } else {
                            const text = await res.text();
                            throw new Error("Server returned HTML instead of JSON. " + text.substring(0, 100));
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            const now = new Date();
                            this.successData = {
                                invoice_no: data.invoice_no,
                                print_url: data.print_url,
                                date: now.toLocaleString(),
                                customer: this.getSelectedCustomer().name,
                                items: JSON.parse(JSON.stringify(this.cart)),
                                subtotal: this.calculateSubtotal(),
                                discount: this.calculateDiscountAmount(),
                                payable: payable,
                                paid: totalPaid,
                                payment_rows: JSON.parse(JSON.stringify(checkoutRows)),
                                change: data.change,
                                points_earned: data.points_earned
                            };

                            // Clear cart and state
                            this.cart = [];
                            this.globalDiscountType = 'fixed';
                            this.globalDiscountValue = 0;
                            this.shippingCost = 0;
                            this.currentHoldId = null;
                            this.showPaymentPopup = false;
                            this.recalculateCartTotals();
                            
                            // Reload updated products list (with modified stock levels and available serials)
                            this.fetchProductsList();

                            if (options.duePrint && data.print_url) {
                                if (options.printWindow && !options.printWindow.closed) {
                                    options.printWindow.location.replace(data.print_url);
                                } else {
                                    window.open(data.print_url, '_blank');
                                }
                            }

                            // Open success screen
                            this.showSuccessModal = true;
                        } else {
                            if (options.printWindow && !options.printWindow.closed) options.printWindow.close();
                            Swal.fire({ text: 'Checkout failed: ' + data.message, icon: 'error' });
                        }
                    })
                    .catch(error => {
                        if (options.printWindow && !options.printWindow.closed) options.printWindow.close();
                        Swal.fire({ text: 'Checkout failed: ' + error.message, icon: 'error' });
                    });
                },

                fetchProductsList() {
                    fetch('{{ route("pos.retail") }}', {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.products) {
                            this.products = data.products;
                        }
                    });
                },

                printA4Invoice(invoice) {
                    if (!invoice || !invoice.print_url) {
                        Swal.fire({ text: 'The A4 invoice link is not available. Please open the invoice from Sales.', icon: 'warning' });
                        return;
                    }
                    window.open(invoice.print_url, '_blank');
                },

                formatMoney(amount) {
                    const value = Number(amount || 0);
                    return value.toLocaleString('en-US', {
                        minimumFractionDigits: this.decimalPlaces,
                        maximumFractionDigits: this.decimalPlaces
                    });
                }
            };
        }
    </script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt-print-area, #receipt-print-area * {
                visibility: visible;
            }
            #receipt-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none !important;
                border: none !important;
                max-height: none !important;
                overflow: visible !important;
                background: transparent !important;
            }
            #receipt-print-area::-webkit-scrollbar {
                display: none;
            }
        }
    </style>
</body>
</html>
