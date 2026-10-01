<x-layouts::app :title="__('Create Stock Transfer')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock-transfers.index') }}" class="p-1.5 text-black hover:text-black hover:bg-slate-100 rounded-lg transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="text-lg font-bold text-black dark:text-white">Create Stock Transfer</h2>
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50" x-data="stockTransferForm()">
        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1 max-w-5xl mx-auto">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('stock-transfers.store') }}" class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf
            
            <div class="lg:col-span-2 space-y-6">
                <!-- Transfer Details -->
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-black">Transfer Details</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">From Store *</label>
                            <select name="from_store_id" x-model="fromStoreId" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                                <option value="">Select Source Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">To Store *</label>
                            <select name="to_store_id" x-model="toStoreId" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                                <option value="">Select Destination Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Transfer Date *</label>
                            <input type="date" name="transfer_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Initial Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed (Deduct Stock Now)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Notes</label>
                            <textarea name="notes" rows="2" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50" placeholder="Optional transfer notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-black">Select Products</h3>
                        <div class="text-[10px] font-medium text-black bg-white px-2.5 py-1 rounded-md border border-slate-200">
                            Items: <span x-text="items.length" class="font-bold text-black">0</span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex gap-2 mb-6">
                            <div class="flex-1 relative">
                                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <select x-model="selectedProduct" class="w-full pl-9 pr-4 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 bg-slate-50" :disabled="!fromStoreId">
                                    <option value="">Search Product...</option>
                                    <template x-for="product in availableProducts" :key="product.id">
                                        <option :value="product.id" x-text="product.name + ' (' + product.sku + ') - Stock: ' + product.current_stock"></option>
                                    </template>
                                </select>
                            </div>
                            <button type="button" @click="addItem()" :disabled="!selectedProduct" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 whitespace-nowrap">
                                Add to List
                            </button>
                        </div>

                        <!-- Items Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 text-black text-[10px] font-bold uppercase tracking-wider bg-slate-100">
                                        <th class="px-4 py-3">Product</th>
                                        <th class="px-4 py-3 w-32 text-right">Avail. Stock</th>
                                        <th class="px-4 py-3 w-32 text-right">Transfer Qty</th>
                                        <th class="px-4 py-3 w-16 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-xs text-black bg-white">
                                    <template x-for="(item, index) in items" :key="item.product_id">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                                <div class="font-bold text-black" x-text="item.name"></div>
                                                <div class="text-[10px] text-black font-mono" x-text="item.sku"></div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="px-2 py-1 bg-slate-100 text-black rounded text-[10px] font-bold" x-text="item.available_stock"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" min="0.01" step="any" :max="item.available_stock" required class="w-full px-2 py-1 border border-slate-900 rounded bg-slate-50 text-right text-sm focus:ring-1 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" @click="removeItem(index)" class="text-rose-400 hover:text-rose-600 transition p-1 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="items.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-black text-xs font-medium">
                                            No products selected yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden sticky top-6">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-black">Action</h3>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <button type="submit" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-blue-500/20 cursor-pointer flex items-center justify-center gap-2" :disabled="items.length === 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                Save Transfer
                            </button>
                            <a href="{{ route('stock-transfers.index') }}" class="w-full flex items-center justify-center px-4 py-3 bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold rounded-xl transition cursor-pointer">
                                Cancel
                            </a>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50/50 rounded-xl border border-blue-100/50">
                            <div class="flex items-start gap-2 text-[10px] text-blue-800">
                                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <strong class="block mb-1">Transfer Logic:</strong>
                                    Creating a transfer with "Pending" status will not affect stock. Moving it to "Completed" will deduct from the source store and add to the destination store immediately.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Alpine Data for dynamic form -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <script>
        const allProducts = @json($products);

        function stockTransferForm() {
            return {
                fromStoreId: '',
                toStoreId: '',
                selectedProduct: '',
                items: [],
                
                get availableProducts() {
                    if (!this.fromStoreId) return [];
                    return allProducts.map(p => {
                        const stockObj = p.store_stocks?.find(s => s.store_id == this.fromStoreId);
                        const stock = stockObj ? parseFloat(stockObj.quantity) : 0;
                        return { ...p, current_stock: stock };
                    }).filter(p => p.current_stock > 0);
                },

                addItem() {
                    if (!this.selectedProduct) return;
                    
                    const product = this.availableProducts.find(p => p.id == this.selectedProduct);
                    if (!product) return;

                    // check if already added
                    if (this.items.find(i => i.product_id == product.id)) {
                        Swal.fire({ icon: 'warning', title: 'Already added', text: 'This product is already in the transfer list.' });
                        this.selectedProduct = '';
                        return;
                    }

                    this.items.push({
                        product_id: product.id,
                        name: product.name,
                        sku: product.sku,
                        available_stock: product.current_stock,
                        quantity: 1
                    });
                    
                    this.selectedProduct = '';
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },
                
                init() {
                    this.$watch('fromStoreId', value => {
                        this.items = []; // reset items when source changes
                    });
                }
            }
        }
    </script>
</x-layouts::app>
