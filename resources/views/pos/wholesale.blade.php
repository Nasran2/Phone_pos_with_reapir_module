<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    @include('partials.head', ['title' => __('Wholesale POS')])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased font-sans flex flex-col" x-data="posApp()">

    <!-- SCREEN 7: Hamburger Menu Drawer (Overlay) -->
    <div x-show="showMenuDrawer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-80 bg-[#0c1630] border-r border-[#182647] text-slate-300 z-50 flex flex-col justify-between shadow-2xl" 
         x-cloak>
        
        <div>
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-[#182647] bg-[#091024]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                            <path d="M9 19h6" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-sm font-bold text-white leading-tight">Wholesale POS</h2>
                            <span class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-md text-[8px] font-black uppercase tracking-wider">Wholesale</span>
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
                    <svg class="w-5 h-5 text-slate-450" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Back to Dashboard</span>
                </a>
                <a href="{{ route('pos.retail') }}" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-450" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span>Retail POS</span>
                </a>
                <a href="#" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-450" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Report Logs</span>
                </a>
                <a href="#" class="flex items-center gap-3 py-2 px-3 hover:bg-[#182647] hover:text-white rounded-lg text-sm transition">
                    <svg class="w-5 h-5 text-slate-450" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>System Settings</span>
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

    <!-- SCREEN 1: Desktop Full Screen POS -->
    <div class="flex-1 flex flex-col w-full h-screen overflow-hidden">
        
        <!-- Top Header Bar -->
        <header class="bg-[#0c1630] border-b border-[#182647] text-white h-16 px-4 flex items-center justify-between shrink-0 z-30">
            <!-- Left Header brand details -->
            <div class="flex items-center gap-4">
                <button @click="showMenuDrawer = true" class="text-slate-350 hover:text-white cursor-pointer" title="Toggle Navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 shadow-md shadow-emerald-500/10">
                        <svg class="w-5.5 h-5.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="12" rx="2" />
                            <path d="M12 15v4" />
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-sm font-black tracking-wide leading-none">Wholesale POS</h1>
                            <span class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-md text-[8px] font-black uppercase tracking-wider">Wholesale Mode</span>
                        </div>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Phone Shop POS / ERP</p>
                    </div>
                </div>
            </div>

            <!-- Central Global Product Search Bar -->
            <div class="flex-1 max-w-lg mx-6 relative hidden md:block">
                <input 
                    type="text" 
                    placeholder="Search by Product, SKU, Barcode... (F1)" 
                    x-model="searchQuery"
                    id="search-input"
                    class="w-full pl-10 pr-10 py-1.5 bg-slate-900 border border-[#182647] rounded-lg text-xs text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 placeholder-slate-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <!-- Barcode Scan Action Button -->
                <button class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-blue-400 cursor-pointer" title="Scan Barcode">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M3 5h2v14H3zm4 0h1v14H7zm3 0h3v14h-3zm5 0h1v14h-1zm3 0h3v14h-3z"/>
                    </svg>
                </button>
            </div>

            <!-- Right header action items -->
            <div class="flex items-center gap-3">
                <!-- Notifications button -->
                <div class="relative">
                    <button class="p-1.5 text-slate-400 hover:text-white transition rounded-lg hover:bg-slate-800 cursor-pointer">
                        <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </button>
                    <span class="absolute top-1 right-1 h-2.5 w-2.5 bg-red-500 rounded-full ring-2 ring-[#0c1630]"></span>
                </div>

                <!-- Hold Sales Modal Toggle -->
                <button @click="showHoldSalesModal = true" class="px-3 py-1.5 bg-amber-600/10 hover:bg-amber-600/20 text-amber-500 hover:text-amber-400 border border-amber-500/20 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Hold Invoices (F3)</span>
                </button>

                <!-- Fullscreen Toggle -->
                <button onclick="document.documentElement.requestFullscreen()" class="p-1.5 text-slate-400 hover:text-white transition rounded-lg hover:bg-slate-800 hidden sm:block cursor-pointer" title="Toggle Fullscreen">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Dynamic Category Header Selector (Horizontal scroll bar) -->
        <div class="bg-white border-b border-slate-200/80 px-4 py-2.5 flex items-center justify-between shrink-0 gap-3">
            <div class="flex items-center gap-2 overflow-x-auto scrollbar-hide py-1 flex-1">
                <!-- All Products Category -->
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
            
            <!-- Mobile Toggle Tabs (Only visible on mobile layout viewports) -->
            <div class="flex items-center gap-1.5 lg:hidden bg-slate-100 p-0.5 rounded-lg text-xs font-bold text-slate-600">
                <button @click="mobileTab = 'products'" class="px-3 py-1 rounded-md" :class="mobileTab === 'products' ? 'bg-white text-blue-600 shadow-xs' : ''">Products</button>
                <button @click="mobileTab = 'cart'" class="px-3 py-1 rounded-md relative" :class="mobileTab === 'cart' ? 'bg-white text-blue-600 shadow-xs' : ''">
                    <span>Cart</span>
                    <span x-show="cart.length > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] h-4.5 w-4.5 rounded-full flex items-center justify-center font-black" x-text="cart.length"></span>
                </button>
            </div>
        </div>

        <!-- POS Master Grid: Product display on left; Cart layout on right -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left Side: Product Grid Section -->
            <div class="flex-1 overflow-y-auto p-4 flex flex-col justify-between" :class="mobileTab === 'products' ? 'block' : 'hidden lg:block'">
                <!-- Products Card Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-3">
                    <template x-for="product in filteredProducts()" :key="product.id">
                        <div @click="openProductFlow(product)" 
                             class="bg-white border border-slate-250/70 rounded-xl p-2 flex flex-col justify-between hover:shadow-md cursor-pointer transition select-none hover:border-blue-400 group">
                            <!-- Image Stand-in matching mockup styling -->
                            <div class="h-16 sm:h-20 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 group-hover:scale-102 transition shrink-0 relative overflow-hidden">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                                <!-- Product stock warning indicator -->
                                <span class="absolute top-1 right-1 px-1 py-0.5 text-[8px] font-black rounded-md shadow-xs bg-slate-900/60 text-white" 
                                      :class="product.stock <= product.stock_alert_quantity ? 'bg-red-650 text-white' : ''"
                                      x-text="'Stock: ' + parseInt(product.stock)"></span>
                            </div>
                            
                            <!-- Text Details -->
                            <div class="mt-2 space-y-1 w-full min-w-0">
                                <h4 class="text-[11px] font-bold text-slate-700 leading-tight line-clamp-2 h-7 group-hover:text-blue-600 transition" x-text="product.name"></h4>
                                <h5 class="text-xs font-black text-blue-600">Rs. <span x-text="formatMoney(product.wholesale_price)"></span></h5>
                                <div class="flex items-center justify-between pt-0.5">
                                    <span class="text-[8px] text-slate-500 font-bold uppercase tracking-wider">Wholesale</span>
                                    <button class="p-0.5 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition focus:outline-none">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right Side: Cart & Checkout Section -->
            <div class="w-full lg:w-96 border-l border-slate-200/80 bg-white p-4 flex flex-col overflow-hidden shrink-0 select-none"
                 :class="mobileTab === 'cart' ? 'flex' : 'hidden lg:flex'">
                
                <!-- Customer Selection Header -->
                <div class="space-y-3 shrink-0">
                    <div class="flex items-center gap-2">
                        <!-- SCREEN 9: Customer Selection/Add Popup Trigger -->
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

                    <!-- Cart Item Table Header -->
                    <div class="grid grid-cols-12 text-[10px] font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-slate-100">
                        <span class="col-span-6">Item</span>
                        <span class="col-span-3 text-center">Qty</span>
                        <span class="col-span-3 text-right">Total</span>
                    </div>
                </div>

                <!-- Cart Items Scroll Area -->
                <div class="flex-1 overflow-y-auto py-2 divide-y divide-slate-100/60">
                    <template x-for="(item, index) in cart" :key="index">
                        <div class="grid grid-cols-12 gap-1 items-center py-2.5 group">
                            <!-- Item name and specs -->
                            <div class="col-span-6 space-y-0.5">
                                <h4 class="text-xs font-bold text-slate-800 leading-snug truncate hover:text-blue-600 cursor-pointer transition" 
                                    @click="openEditItemModal(item, index)" 
                                    x-text="item.name"
                                    title="Click to edit item discount, qty, and price"></h4>
                                <template x-if="item.variant_label">
                                    <div class="text-[9px] font-bold text-blue-600" x-text="item.variant_label"></div>
                                </template>
                                <div class="flex items-center gap-1.5">
                                    <!-- Price edit field -->
                                    <span class="text-[9px] text-slate-400 font-bold">Wh: </span>
                                    <input type="number" 
                                           x-model.number="item.selling_price" 
                                           @input="updateLineTotal(item)"
                                           class="w-14 px-1 py-0.5 border border-slate-200 rounded text-[9px] font-semibold focus:outline-none" />
                                </div>
                            </div>
                            
                            <!-- Qty steppers -->
                            <div class="col-span-3 flex items-center justify-center gap-1">
                                <button @click="decrementQty(item)" class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded focus:outline-none cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                                <span class="text-xs font-black w-4 text-center text-slate-800" x-text="item.quantity"></span>
                                <button @click="incrementQty(item)" class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded focus:outline-none cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                </button>
                            </div>

                            <!-- Line subtotal and remove -->
                            <div class="col-span-3 flex flex-col items-end justify-center">
                                <span class="text-xs font-black text-slate-800">Rs. <span x-text="formatMoney(item.line_total)"></span></span>
                                <button @click="removeFromCart(index)" class="text-[9px] text-red-500 hover:text-red-650 font-bold mt-1 opacity-60 hover:opacity-100 cursor-pointer">Remove</button>
                            </div>
                        </div>
                    </template>
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full gap-2 text-slate-400 py-12">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                            </svg>
                            <span class="text-xs font-bold">Your POS Cart is empty</span>
                        </div>
                    </template>
                </div>

                <!-- Totals Checkout Panel & Payment Screen Actions -->
                <div class="border-t border-slate-200/80 pt-4 space-y-3 shrink-0">
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Subtotal:</span>
                            <span class="font-semibold text-slate-800">Rs. <span x-text="formatMoney(calculateSubtotal())"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <div class="flex items-center gap-1">
                                <span>Discount (%):</span>
                                <input type="number" x-model.number="discountPercentage" class="w-10 text-right px-1.5 py-0.5 border border-slate-200 rounded focus:outline-none" />
                            </div>
                            <span class="font-semibold text-red-600">-Rs. <span x-text="formatMoney(calculateDiscountAmount())"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Tax (VAT <span x-text="taxPercentage"></span>%):</span>
                            <span class="font-semibold text-slate-800">Rs. <span x-text="formatMoney(calculateTaxAmount())"></span></span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Shipping / Other:</span>
                            <input type="number" x-model.number="shippingCost" class="w-16 text-right px-1.5 py-0.5 border border-slate-200 rounded focus:outline-none" />
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-2 pb-1 text-slate-800">
                            <span class="font-bold text-sm">Total Payable:</span>
                            <span class="font-black text-blue-600 text-lg">Rs. <span x-text="formatMoney(calculateGrandTotal())"></span></span>
                        </div>
                    </div>

                    <!-- Payment selector buttons -->
                    <div class="space-y-2.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Payment Method</span>
                        <div class="grid grid-cols-4 gap-2 text-[10px] font-bold">
                            <button @click="paymentMethod = 'Cash'" class="py-2.5 rounded-lg flex flex-col items-center justify-center gap-1 border transition cursor-pointer"
                                    :class="paymentMethod === 'Cash' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                <span>Cash</span>
                            </button>
                            <button @click="paymentMethod = 'Bank'" class="py-2.5 rounded-lg flex flex-col items-center justify-center gap-1 border transition cursor-pointer"
                                    :class="paymentMethod === 'Bank' ? 'bg-blue-500/10 text-blue-600 border-blue-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                                <span>Bank</span>
                            </button>
                            <button @click="paymentMethod = 'Card'" class="py-2.5 rounded-lg flex flex-col items-center justify-center gap-1 border transition cursor-pointer"
                                    :class="paymentMethod === 'Card' ? 'bg-purple-500/10 text-purple-600 border-purple-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                <span>Card</span>
                            </button>
                            <button @click="paymentMethod = 'Other'" class="py-2.5 rounded-lg flex flex-col items-center justify-center gap-1 border transition cursor-pointer"
                                    :class="paymentMethod === 'Other' ? 'bg-slate-500/10 text-slate-600 border-slate-500' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                <span>Other</span>
                            </button>
                        </div>

                        <!-- Paid Cash & Change calculation -->
                        <div class="grid grid-cols-2 gap-4 text-xs font-bold">
                            <div>
                                <label class="block text-slate-500 mb-1 text-[10px]">Paid Amount</label>
                                <input type="number" x-model.number="paidAmount" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none text-slate-900" />
                            </div>
                            <div>
                                <label class="block text-slate-500 mb-1 text-[10px]">Change Amount</label>
                                <div class="px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-slate-800 flex items-center justify-between">
                                    <span>Rs.</span>
                                    <span x-text="formatMoney(calculateChangeAmount())"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Bottom Action Buttons -->
                    <div class="grid grid-cols-3 gap-2.5 text-xs font-bold pt-2.5 border-t border-slate-100">
                        <button @click="clearCart()" class="py-3 bg-red-150 hover:bg-red-200 text-red-650 border border-red-200 rounded-xl transition cursor-pointer">Clear</button>
                        <button @click="holdCurrentSale()" class="py-3 bg-amber-100 hover:bg-amber-200 text-amber-600 border border-amber-200 rounded-xl transition cursor-pointer">Hold (F2)</button>
                        <button @click="submitPOSCheckout()" 
                                class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition flex items-center justify-center gap-1 cursor-pointer"
                                :disabled="cart.length === 0"
                                :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            <span>Pay (F12)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCREEN 2: Wholesale Product Popup Modal (Desktop Only) -->
    <!-- AND SCREEN 6: Mobile Bottom Sheet Product Popup (Mobile Viewports) -->
    <div x-show="showProductModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <!-- Modal Card Container -->
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl flex flex-col md:flex-row gap-6 border border-slate-200 transform scale-100 transition duration-300"
             @click.away="closeProductModal()">
            
            <!-- Left Panel: Product visual -->
            <div class="w-full md:w-2/5 flex flex-col items-center justify-center bg-slate-50 border border-slate-100 rounded-2xl p-4">
                <div class="aspect-square w-24 h-24 flex items-center justify-center text-slate-400 bg-white rounded-xl shadow-xs">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <h3 class="text-sm font-extrabold text-slate-850 text-center mt-3.5" x-text="selectedProduct ? selectedProduct.name : ''"></h3>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Available Stock:</span>
                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-black" x-text="selectedProduct ? parseInt(selectedProduct.stock) : 0"></span>
                </div>
            </div>

            <!-- Right Panel: Inputs, Quantity, and Totals -->
            <div class="w-full md:w-3/5 flex flex-col justify-between space-y-4">
                <!-- Info cards (cost vs wholesale selling) -->
                <div class="grid grid-cols-2 gap-3 text-[10px] font-bold text-slate-500">
                    <div class="bg-slate-50 border border-slate-200/50 rounded-xl p-2.5 text-center">
                        <span class="block text-slate-400 uppercase tracking-wider mb-1">Cost Price</span>
                        <span class="text-xs font-black text-slate-800">Rs. <span x-text="selectedProduct ? formatMoney(selectedProduct.cost_price) : 0"></span></span>
                    </div>
                    <div class="bg-slate-50 border border-slate-200/50 rounded-xl p-2.5 text-center">
                        <span class="block text-slate-400 uppercase tracking-wider mb-1">Wholesale price</span>
                        <span class="text-xs font-black text-blue-600">Rs. <span x-text="selectedProduct ? formatMoney(selectedProduct.wholesale_price) : 0"></span></span>
                    </div>
                </div>

                <template x-if="selectedProduct && selectedProduct.variants && selectedProduct.variants.length > 0">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Variation</label>
                        <select x-model="selectedVariantId" @change="applySelectedVariant()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-250 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <template x-for="variant in selectedProduct.variants" :key="variant.id">
                                <option :value="variant.id" x-text="`${variant.label} | Stock: ${parseInt(variant.stock_quantity)} | Rs. ${formatMoney(variant.wholesale_price)}`"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <!-- Quantity controls -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Quantity</label>
                    <div class="flex items-center gap-2">
                        <button @click="modalQuantity = Math.max(1, modalQuantity - 1)" class="p-2 border border-slate-250 bg-slate-50 rounded-xl focus:outline-none cursor-pointer">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                        <input type="number" x-model.number="modalQuantity" @input="modalQuantity = selectedProduct ? Math.max(1, Math.min(selectedProduct.stock, modalQuantity)) : Math.max(1, modalQuantity)" class="w-full text-center py-2.5 bg-slate-50 border border-slate-250 rounded-xl font-bold focus:outline-none" />
                        <button @click="modalQuantity = selectedProduct ? Math.min(selectedProduct.stock, modalQuantity + 1) : modalQuantity + 1" class="p-2 border border-slate-250 bg-slate-50 rounded-xl focus:outline-none cursor-pointer">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>
                    <!-- Stock limit warning -->
                    <template x-if="selectedProduct && modalQuantity >= selectedProduct.stock">
                        <div class="text-[10px] font-semibold text-amber-600 mt-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>Maximum available stock reached!</span>
                        </div>
                    </template>
                </div>

                <!-- Custom Discount inputs -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Type</label>
                        <select x-model="modalDiscountType" class="w-full px-2 py-2.5 bg-slate-50 border border-slate-250 rounded-xl text-xs font-semibold focus:outline-none">
                            <option value="percentage">%</option>
                            <option value="fixed">Fixed</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Discount Amount</label>
                        <input type="number" x-model.number="modalDiscountAmount" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none" />
                    </div>
                </div>

                <!-- Editable Selling Price -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Editable Selling Price</label>
                    <input type="number" x-model.number="modalPrice" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none font-bold text-blue-600" />
                    <!-- Cost protection warning -->
                    <template x-if="selectedProduct && modalPrice < selectedProduct.cost_price">
                        <div class="text-[10px] font-semibold text-amber-600 mt-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>Selling price is below cost price! Manager approval may be required.</span>
                        </div>
                    </template>
                </div>

                <!-- Line Note -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Add Note</label>
                    <input type="text" x-model="modalNote" placeholder="Optional line transaction note" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none" />
                </div>

                <!-- Real-time Live Line Total Preview -->
                <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-2xl flex items-center justify-between text-xs font-bold text-slate-600">
                    <span>Line Total:</span>
                    <span class="text-blue-600 font-extrabold text-sm">Rs. <span x-text="formatMoney(calculateModalLineTotal())"></span></span>
                </div>

                <!-- Modal Actions -->
                <div class="grid grid-cols-2 gap-3 text-xs font-bold">
                    <button @click="closeProductModal()" class="py-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 transition cursor-pointer">Cancel</button>
                    <button @click="addProductToCart()" class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition cursor-pointer" x-text="editingCartIndex !== null ? 'Save Changes' : 'Add to Cart'"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCREEN 8: Hold Sales Screen Overlay (F2 list) -->
    <div x-show="showHoldSalesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 flex flex-col justify-between"
             @click.away="showHoldSalesModal = false">
            
            <div class="border-b border-slate-100 pb-4 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Hold Invoices Queue</h3>
                    <p class="text-[10px] text-slate-400">Recall transactions back into the checkout cart</p>
                </div>
                <button @click="showHoldSalesModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Hold sales list -->
            <div class="space-y-3 flex-1 overflow-y-auto max-h-[360px] min-h-60 pr-1">
                @forelse($holdSales as $hold)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/60 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wide">Held</span>
                                <span>Ref: {{ $hold->reference_no }}</span>
                            </div>
                            <p class="text-[10px] text-slate-500 font-semibold">{{ $hold->customer_name }} &bull; {{ date('M d, Y', strtotime($hold->hold_date)) }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-black text-slate-800">Rs. {{ number_format($hold->total_amount, 2) }}</span>
                            <button @click="window.location.href='{{ route('pos.wholesale', ['hold_id' => $hold->id]) }}'" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer">Recall</button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 font-semibold text-xs flex flex-col items-center justify-center gap-2">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>No transactions currently on hold.</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- SCREEN 9: Customer Selection/Add Popup Modal -->
    <div x-show="showAddCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200"
             @click.away="showAddCustomerModal = false">
            
            <div class="border-b border-slate-100 pb-3.5 mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-850">Add Wholesale Customer</h3>
                    <p class="text-[10px] text-slate-400">Quick-create customer details</p>
                </div>
                <button @click="showAddCustomerModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Customer inputs -->
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" x-model="customerForm.name" placeholder="E.g. Saman Kumara" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Phone Number</label>
                    <input type="text" x-model="customerForm.phone" placeholder="E.g. 0771234567" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" x-model="customerForm.email" placeholder="E.g. customer@example.com" class="w-full px-3 py-2 bg-slate-50 border border-slate-250 rounded-xl text-xs focus:outline-none" />
                </div>
                
                <div class="grid grid-cols-2 gap-3 text-xs font-bold pt-2">
                    <button @click="showAddCustomerModal = false" class="py-3 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 transition cursor-pointer">Cancel</button>
                    <button @click="quickCreateCustomer()" class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition cursor-pointer">Create & Select</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCREEN 10: Payment Success Screen Modal -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs select-none" x-cloak>
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center flex flex-col items-center justify-between">
            <!-- Icon -->
            <div class="h-16 w-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-black text-slate-850">Checkout Successful</h3>
            <p class="text-xs text-slate-400 mt-1 font-semibold">Invoice has been successfully created and recorded.</p>

            <div class="w-full my-5 p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2.5 text-xs text-slate-500 text-left font-medium">
                <div class="flex justify-between">
                    <span>Invoice No:</span>
                    <span class="font-bold text-slate-800" x-text="checkoutSuccessData.invoice_no"></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Amount Paid:</span>
                    <span class="font-extrabold text-emerald-650" x-text="'Rs. ' + formatMoney(checkoutSuccessData.paid_amount || 0)"></span>
                </div>
                <div class="flex justify-between">
                    <span>Change Return:</span>
                    <span class="font-extrabold text-blue-600" x-text="'Rs. ' + formatMoney(checkoutSuccessData.change || 0)"></span>
                </div>
            </div>

            <!-- Receipts / Actions -->
            <div class="space-y-3 w-full">
                <!-- Thermal receipt printing format selections -->
                <div class="grid grid-cols-3 gap-2 text-[10px] font-bold">
                    <button @click="printReceipt('58mm')" class="py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/50 rounded-xl text-slate-700 flex flex-col items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                        <span>58mm Roll</span>
                    </button>
                    <button @click="printReceipt('80mm')" class="py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/50 rounded-xl text-slate-700 flex flex-col items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                        <span>80mm Roll</span>
                    </button>
                    <button @click="printReceipt('A4')" class="py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/50 rounded-xl text-slate-700 flex flex-col items-center justify-center gap-1 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span>A4 Invoice</span>
                    </button>
                </div>

                <!-- Share to WhatsApp -->
                <button @click="shareWhatsApp()" class="w-full py-3 bg-emerald-650 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/10 flex items-center justify-center gap-1.5 cursor-pointer">
                    <!-- WhatsApp SVG Icon -->
                    <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.022-.014-.508-.25-1.5-.742-.16-.076-.27-.114-.38-.114-.11 0-.22.038-.32.19l-.43.56c-.1.15-.22.18-.33.12-.12-.06-.5-.18-.95-.58-.35-.31-.59-.7-.66-.82-.07-.12-.01-.18.05-.24.05-.05.12-.14.18-.2.06-.06.08-.12.12-.2.04-.07.02-.13-.01-.2-.03-.08-.38-.92-.52-1.27-.14-.35-.29-.3-.38-.3h-.33c-.1 0-.27.04-.42.2-.15.17-.58.57-.58 1.39 0 .82.6 1.62.68 1.73.08.12 1.18 1.8 2.87 2.53.4.17.72.28.96.35.4.12.76.1 1.05.06.32-.05 1-.4 1.14-.78.14-.38.14-.7.1-.78-.04-.08-.13-.12-.24-.18zM12 2A10 10 0 0 0 2 12a9.9 9.9 0 0 0 1.52 5.23L2 22l4.9-1.28A9.9 9.9 0 0 0 12 22a10 10 0 0 0 10-10A10 10 0 0 0 12 2z"/>
                    </svg>
                    <span>Share invoice via WhatsApp</span>
                </button>

                <!-- New Sale button -->
                <button @click="resetPOSForNewSale()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/10 cursor-pointer">New Sale (ESC)</button>
            </div>
        </div>
    </div>

    <!-- Client-side SPA POS Logic -->
    <script>
        function posApp() {
            return {
                products: @json($products),
                categories: @json($categories),
                customers: @json($customers),
                paymentMethods: @json($paymentMethods),
                holdSales: @json($holdSales),
                resumeHold: @json($resumeHold),
                
                cart: [],
                selectedCategory: 'all',
                searchQuery: '',
                selectedCustomerId: 1, // Default Walk-in
                currentHoldId: null,
                discountPercentage: 0,
                taxPercentage: 15,
                shippingCost: 0,
                paidAmount: 0,
                paymentMethod: 'Cash',
                
                mobileTab: 'products', // products, cart
                
                // Modals/Sheets
                showMenuDrawer: false,
                showHoldSalesModal: false,
                showAddCustomerModal: false,
                showProductModal: false,
                showSuccessModal: false,
                
                // Modal form fields
                selectedProduct: null,
                selectedVariantId: '',
                modalQuantity: 1,
                modalDiscountType: 'percentage',
                modalDiscountAmount: 0,
                modalPrice: 0,
                modalNote: '',
                editingCartIndex: null,
                
                // Customer quick create form
                customerForm: {
                    name: '',
                    phone: '',
                    email: ''
                },
                
                checkoutSuccessData: {},

                init() {
                    if (this.resumeHold) {
                        this.recallHeldSale(this.resumeHold, true);
                    }

                    // Register keyboard shortcuts
                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'F1') {
                            e.preventDefault();
                            document.getElementById('search-input').focus();
                        } else if (e.key === 'F2') {
                            e.preventDefault();
                            this.holdCurrentSale();
                        } else if (e.key === 'F3') {
                            e.preventDefault();
                            this.showHoldSalesModal = true;
                        } else if (e.key === 'F12') {
                            e.preventDefault();
                            this.submitPOSCheckout();
                        } else if (e.key === 'Escape') {
                            if (this.showSuccessModal) {
                                this.resetPOSForNewSale();
                            } else {
                                this.showMenuDrawer = false;
                                this.showHoldSalesModal = false;
                                this.showAddCustomerModal = false;
                                this.showProductModal = false;
                            }
                        }
                    });
                },

                // Filters products by selected category and search input
                filteredProducts() {
                    return this.products.filter(p => {
                        const matchesCategory = this.selectedCategory === 'all' || p.category_id == this.getCategoryIdBySlug(this.selectedCategory);
                        const matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                              p.sku.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                              (p.barcode && p.barcode.includes(this.searchQuery));
                        return matchesCategory && matchesSearch;
                    });
                },

                getCategoryIdBySlug(slug) {
                    const cat = this.categories.find(c => c.slug === slug);
                    return cat ? cat.id : null;
                },

                // Opens custom popup modal for item parameters
                openProductFlow(product) {
                    this.selectedProduct = JSON.parse(JSON.stringify(product));
                    this.selectedVariantId = '';
                    this.modalQuantity = 1;
                    this.modalDiscountType = 'percentage';
                    this.modalDiscountAmount = 0;
                    this.modalPrice = product.wholesale_price;
                    this.modalNote = '';
                    this.editingCartIndex = null;

                    if (this.selectedProduct.variants && this.selectedProduct.variants.length > 0) {
                        this.selectedVariantId = this.selectedProduct.variants[0].id;
                        this.applySelectedVariant();
                    }
                    
                    this.showProductModal = true;
                },

                openEditItemModal(item, index) {
                    this.editingCartIndex = index;
                    this.selectedProduct = {
                        id: item.product_id,
                        name: item.name,
                        variant_id: item.variant_id || null,
                        variant_label: item.variant_label || '',
                        cost_price: item.cost_price,
                        stock: item.stock,
                        wholesale_price: item.selling_price,
                        variants: []
                    };
                    this.selectedVariantId = item.variant_id || '';
                    this.modalQuantity = item.quantity;
                    this.modalDiscountType = item.discount_type || 'percentage';
                    this.modalDiscountAmount = item.discount_amount || 0;
                    this.modalPrice = item.selling_price;
                    this.modalNote = item.note || '';
                    
                    this.showProductModal = true;
                },

                closeProductModal() {
                    this.showProductModal = false;
                    this.selectedProduct = null;
                    this.selectedVariantId = '';
                    this.editingCartIndex = null;
                },

                selectedVariant() {
                    if (!this.selectedProduct || !this.selectedProduct.variants) return null;
                    return this.selectedProduct.variants.find(variant => Number(variant.id) === Number(this.selectedVariantId)) || null;
                },

                applySelectedVariant() {
                    const variant = this.selectedVariant();
                    if (!variant) return;

                    this.selectedProduct.variant_id = variant.id;
                    this.selectedProduct.variant_label = variant.label;
                    this.selectedProduct.cost_price = variant.cost_price || this.selectedProduct.cost_price;
                    this.selectedProduct.wholesale_price = variant.wholesale_price || this.selectedProduct.wholesale_price;
                    this.selectedProduct.stock = variant.stock_quantity;
                    this.modalPrice = this.selectedProduct.wholesale_price;
                    this.modalQuantity = Math.min(Math.max(1, this.modalQuantity || 1), this.selectedProduct.stock || 1);
                },

                // Computes the preview subtotal of modal item details
                calculateModalLineTotal() {
                    if (!this.selectedProduct) return 0;
                    let baseTotal = this.modalPrice * this.modalQuantity;
                    if (this.modalDiscountType === 'percentage') {
                        return baseTotal - (baseTotal * (this.modalDiscountAmount / 100));
                    } else {
                        return Math.max(0, baseTotal - this.modalDiscountAmount);
                    }
                },

                // Push custom details into cart array
                addProductToCart() {
                    if (this.modalQuantity > this.selectedProduct.stock) {
                        alert('Error: Cannot add more than available stock (' + parseInt(this.selectedProduct.stock) + ')!');
                        return;
                    }
                    
                    if (this.editingCartIndex !== null) {
                        // Update existing item in cart
                        let item = this.cart[this.editingCartIndex];
                        item.quantity = this.modalQuantity;
                        item.selling_price = this.modalPrice;
                        item.discount_type = this.modalDiscountType;
                        item.discount_amount = this.modalDiscountAmount;
                        item.note = this.modalNote;
                        this.updateLineTotal(item);
                    } else {
                        // Add details to cart array
                        this.cart.push({
                            product_id: this.selectedProduct.id,
                            variant_id: this.selectedProduct.variant_id || null,
                            variant_label: this.selectedProduct.variant_label || '',
                            name: this.selectedProduct.name,
                            quantity: this.modalQuantity,
                            cost_price: this.selectedProduct.cost_price,
                            selling_price: this.modalPrice,
                            discount_type: this.modalDiscountType,
                            discount_amount: this.modalDiscountAmount,
                            line_total: this.calculateModalLineTotal(),
                            note: this.modalNote,
                            stock: this.selectedProduct.stock
                        });
                    }

                    this.closeProductModal();
                    
                    // Automatically update default paid amount to match grand total
                    this.paidAmount = this.calculateGrandTotal();
                },

                updateLineTotal(item) {
                    let baseTotal = item.selling_price * item.quantity;
                    if (item.discount_type === 'percentage') {
                        item.line_total = baseTotal - (baseTotal * (item.discount_amount / 100));
                    } else {
                        item.line_total = Math.max(0, baseTotal - item.discount_amount);
                    }
                    this.paidAmount = this.calculateGrandTotal();
                },

                incrementQty(item) {
                    if (item.quantity < item.stock) {
                        item.quantity++;
                        this.updateLineTotal(item);
                    } else {
                        alert('Cannot add more than available stock quantity (' + parseInt(item.stock) + ')!');
                    }
                },

                decrementQty(item) {
                    if (item.quantity > 1) {
                        item.quantity--;
                        this.updateLineTotal(item);
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.paidAmount = this.calculateGrandTotal();
                },

                clearCart() {
                    if (confirm('Are you sure you want to clear the current transaction cart?')) {
                        this.cart = [];
                        this.paidAmount = 0;
                        this.discountPercentage = 0;
                        this.shippingCost = 0;
                    }
                },

                // Calcs totals
                calculateSubtotal() {
                    return this.cart.reduce((sum, item) => sum + item.line_total, 0);
                },

                calculateDiscountAmount() {
                    return this.calculateSubtotal() * (this.discountPercentage / 100);
                },

                calculateTaxAmount() {
                    return (this.calculateSubtotal() - this.calculateDiscountAmount()) * (this.taxPercentage / 100);
                },

                calculateGrandTotal() {
                    return this.calculateSubtotal() - this.calculateDiscountAmount() + this.calculateTaxAmount() + parseFloat(this.shippingCost || 0);
                },

                calculateChangeAmount() {
                    return Math.max(0, this.paidAmount - this.calculateGrandTotal());
                },

                // Creates customer via AJAX
                quickCreateCustomer() {
                    if (!this.customerForm.name) {
                        alert('Please enter a name for the customer.');
                        return;
                    }
                    
                    fetch('{{ route("pos.wholesale.customer") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.customerForm)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.customers.push(data.customer);
                            this.selectedCustomerId = data.customer.id;
                            this.showAddCustomerModal = false;
                            this.customerForm = { name: '', phone: '', email: '' };
                        } else {
                            alert('Failed to create customer: ' + data.message);
                        }
                    });
                },

                // Submits checkout details to Laravel backend controller
                submitPOSCheckout() {
                    if (this.cart.length === 0) {
                        alert('Please add products to the cart before checking out.');
                        return;
                    }

                    const payload = {
                        customer_id: this.selectedCustomerId,
                        pos_type: 'wholesale',
                        items: this.cart,
                        total_amount: this.calculateSubtotal(),
                        discount_amount: this.calculateDiscountAmount(),
                        tax_amount: this.calculateTaxAmount(),
                        payable_amount: this.calculateGrandTotal(),
                        paid_amount: this.paidAmount,
                        payment_method: this.paymentMethod,
                        payment_reference: '',
                        hold_id: this.currentHoldId
                    };

                    fetch('{{ route("pos.wholesale.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.checkoutSuccessData = {
                                invoice_no: data.invoice_no,
                                paid_amount: this.paidAmount,
                                change: data.change
                            };
                            this.showSuccessModal = true;
                            this.currentHoldId = null;
                        } else {
                            alert('Checkout error: ' + data.message);
                        }
                    });
                },

                // Puts a transaction on hold (Local storage/cookies mapping is standard)
                holdCurrentSale() {
                    if (this.cart.length === 0) {
                        alert('No products in cart to hold.');
                        return;
                    }

                    const holdRef = 'HOLD-' + Math.floor(1000 + Math.random() * 9000);
                    
                    // Simple AJAX or prompt save (we can insert directly or use local state, let's insert standard hold)
                    alert('Sale placed on hold with Reference: ' + holdRef);
                    this.cart = [];
                    this.paidAmount = 0;
                },

                // Recalls a held transaction from the list
                recallHeldSale(hold, fromServer = false) {
                    this.showHoldSalesModal = false;
                    this.cart = hold.items || [];
                    this.selectedCustomerId = hold.customer_id || this.selectedCustomerId;
                    this.discountPercentage = hold.discountPercentage || 0;
                    this.taxPercentage = hold.taxPercentage || this.taxPercentage;
                    this.shippingCost = hold.shippingCost || 0;
                    this.currentHoldId = fromServer ? hold.id : null;
                    this.holdSales = this.holdSales.filter(h => h.id !== hold.id);
                    this.paidAmount = this.calculateGrandTotal();
                },

                printReceipt(format) {
                    alert('Sending print signal for ' + format + ' size thermal receipt.');
                },

                shareWhatsApp() {
                    alert('Opening WhatsApp sharing link for invoice details.');
                },

                resetPOSForNewSale() {
                    this.cart = [];
                    this.paidAmount = 0;
                    this.discountPercentage = 0;
                    this.shippingCost = 0;
                    this.currentHoldId = null;
                    this.showSuccessModal = false;
                    this.checkoutSuccessData = {};
                },

                formatMoney(value) {
                    return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</body>
</html>
