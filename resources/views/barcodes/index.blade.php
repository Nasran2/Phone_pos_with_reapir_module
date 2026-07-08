<x-layouts::app :title="__('Print Barcodes')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
            </svg>
            Print Barcodes
        </h2>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50" x-data="barcodeForm()">
        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium max-w-5xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800">✕</button>
        </div>
        @endif

        <form method="POST" action="{{ route('barcodes.print') }}" target="_blank" class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf
            
            <div class="lg:col-span-2 space-y-6">
                <!-- Product Selection -->
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800">Select Products to Print</h3>
                        <div class="text-[10px] font-medium text-slate-500 bg-white px-2.5 py-1 rounded-md border border-slate-200">
                            Products: <span x-text="items.length" class="font-bold text-slate-700">0</span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex gap-2 mb-6">
                            <div class="flex-1 relative">
                                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <select x-model="selectedProduct" class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 bg-slate-50">
                                    <option value="">Search Product...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-sku="{{ $product->sku }}">{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" @click="addItem()" :disabled="!selectedProduct" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition disabled:opacity-50 whitespace-nowrap">
                                Add
                            </button>
                        </div>

                        <!-- Items Table -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-500 text-[10px] font-bold uppercase tracking-wider bg-slate-100">
                                        <th class="px-4 py-3">Product</th>
                                        <th class="px-4 py-3 w-32 text-right">No. of Labels</th>
                                        <th class="px-4 py-3 w-16 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-xs text-slate-700 bg-white">
                                    <template x-for="(item, index) in items" :key="item.product_id">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                                <div class="font-bold text-slate-800" x-text="item.name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-text="item.sku"></div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" min="1" step="1" required class="w-full px-2 py-1 border border-slate-200 rounded bg-slate-50 text-right text-sm focus:ring-1 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" @click="removeItem(index)" class="text-rose-400 hover:text-rose-600 transition p-1 cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="items.length === 0">
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-400 text-xs font-medium">
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
                        <h3 class="text-sm font-bold text-slate-800">Print Settings</h3>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Paper / Label Size</label>
                            <select name="paper_size" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 bg-slate-50">
                                <option value="a4">A4 Sheet (Standard 40 labels)</option>
                                <option value="thermal_40_20">Thermal Roll (40mm x 20mm)</option>
                                <option value="thermal_50_25">Thermal Roll (50mm x 25mm)</option>
                            </select>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-blue-500/20 cursor-pointer flex items-center justify-center gap-2" :disabled="items.length === 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Generate PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Alpine Data for dynamic form -->
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function barcodeForm() {
            return {
                selectedProduct: '',
                items: [],
                
                addItem() {
                    if (!this.selectedProduct) return;
                    
                    const select = document.querySelector('select[x-model="selectedProduct"]');
                    const option = select.options[select.selectedIndex];
                    
                    const id = option.value;
                    const name = option.getAttribute('data-name');
                    const sku = option.getAttribute('data-sku');

                    // check if already added
                    if (this.items.find(i => i.product_id == id)) {
                        Swal.fire({ icon: 'warning', title: 'Already added', text: 'This product is already in the list. Please adjust the quantity.' });
                        this.selectedProduct = '';
                        return;
                    }

                    this.items.push({
                        product_id: id,
                        name: name,
                        sku: sku,
                        quantity: 1
                    });
                    
                    this.selectedProduct = '';
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-layouts::app>
