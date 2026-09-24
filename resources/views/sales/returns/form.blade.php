<x-layouts::app :title="__('Create Sale Return')">
    <div x-data="returnProcess()" class="flex flex-col h-full bg-slate-50 min-h-screen">
        <div class="px-6 py-8 md:px-8 max-w-7xl mx-auto w-full flex flex-wrap gap-3 items-start justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-850">POS Returns</h2>
                <p class="text-sm text-slate-500 mt-1">Exchange same products, refund paid bills, or reduce customer due balances.</p>
            </div>
            <a href="{{ route('sales-returns.index') }}" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-bold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Sales List
            </a>
        </div>

        <div class="px-6 md:px-8 space-y-6 flex-1 max-w-7xl mx-auto w-full">
            @if($errors->any())
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-sm font-semibold shadow-xs">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('sales-returns.store') }}" id="returnForm">
                @csrf
                <input type="hidden" name="source_type" x-model="sourceType">
                <input type="hidden" name="sale_id" :value="selectedSale ? selectedSale.id : ''">
                <input type="hidden" name="customer_id" :value="selectedCustomerId">
                <input type="hidden" name="return_type" x-model="returnType">

                <!-- INVOICE SEARCH GRID (Shown when no sale is selected) -->
                <div x-show="!selectedSale && sourceType === 'invoice'" class="space-y-6" x-transition>
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" x-model="search" placeholder="Search invoice, customer, or phone..." class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-xs text-sm">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 max-h-[500px] overflow-y-auto p-1">
                            <template x-for="sale in filteredSales" :key="sale.id">
                                <div @click="loadSale(sale.id)" class="border border-slate-200 hover:border-purple-300 hover:shadow-md bg-white rounded-xl p-4 cursor-pointer transition transform hover:-translate-y-0.5">
                                    <div class="flex justify-between items-start">
                                        <span class="font-bold text-slate-850" x-text="sale.invoice_no"></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" 
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': sale.payment_status === 'Paid',
                                                'bg-emerald-50 text-emerald-600': sale.payment_status === 'Cheque Pending' || sale.payment_status === 'CHEQUE_PENDING',
                                                'bg-red-100 text-red-700': sale.payment_status === 'Due',
                                                'bg-orange-100 text-orange-700': sale.payment_status === 'Partial'
                                            }" x-text="sale.payment_status ? sale.payment_status.replace('_', ' ') : 'PAID'"></span>
                                    </div>
                                    <div class="text-sm text-slate-500 mt-1 truncate" x-text="sale.customer ? sale.customer.name : 'Walk-in Customer'"></div>
                                    <div class="flex justify-between items-end mt-4 text-xs">
                                        <span class="text-slate-400" x-text="sale.created_at.substring(0, 10)"></span>
                                        <span class="font-black text-slate-800" x-text="'Rs ' + formatCurrency(sale.payable_amount || sale.total_amount)"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredSales.length === 0" class="col-span-full py-8 text-center text-slate-500 text-sm font-semibold bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                No matching invoices found for this search.
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center text-slate-400 shadow-sm">
                        <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                        <span class="font-bold text-slate-500">Choose an invoice to start a return.</span>
                        <button type="button" @click="sourceType = 'old'" class="mt-4 text-xs font-bold text-purple-600 hover:text-purple-800 underline">Or click here for a legacy return without invoice</button>
                    </div>
                </div>

                <!-- SELECTED INVOICE HEADER (Shown when an invoice is selected) -->
                <div x-show="selectedSale && sourceType === 'invoice'" class="bg-white rounded-xl border border-purple-200 p-5 shadow-xs mb-6 flex justify-between items-center bg-gradient-to-r from-purple-50 to-white" x-transition>
                    <div>
                        <p class="text-xs font-bold text-purple-600 uppercase tracking-wider mb-1">Returning From Invoice</p>
                        <h3 class="font-black text-xl text-slate-850" x-text="selectedSale ? selectedSale.invoice_no : ''"></h3>
                        <p class="text-sm text-slate-600 mt-0.5" x-text="selectedSale && selectedSale.customer ? selectedSale.customer.name + ' (' + selectedSale.customer.phone + ')' : 'Walk-in Customer'"></p>
                    </div>
                    <button type="button" @click="window.location.href = window.location.pathname" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-xs transition">Change Invoice</button>
                </div>

                <!-- LEGACY RETURN HEADER (Shown when old return is selected) -->
                <div x-show="sourceType === 'old'" class="bg-white rounded-xl border border-yellow-200 p-5 shadow-xs mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-yellow-50 to-white" x-transition>
                    <div>
                        <p class="text-xs font-bold text-yellow-600 uppercase tracking-wider mb-1">Legacy / Old Return</p>
                        <h3 class="font-black text-xl text-slate-850">No Invoice Selected</h3>
                        <p class="text-sm text-slate-600 mt-0.5">Please select the customer making the return.</p>
                    </div>
                    
                    <div class="w-full md:w-1/3 relative" x-data="{ open: false, searchCustomer: '' }">
                        <div class="w-full px-3 py-2.5 border border-yellow-300 rounded-lg text-sm bg-white shadow-sm focus-within:ring-2 focus-within:ring-yellow-500 focus-within:border-yellow-500 cursor-pointer flex justify-between items-center" @click="open = !open">
                            <span class="truncate font-semibold text-slate-800" x-text="selectedCustomerName()"></span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        
                        <div x-show="open" @click.away="open = false" class="absolute z-40 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl overflow-hidden" x-transition>
                            <div class="p-2 border-b border-slate-100 bg-slate-50">
                                <input type="text" x-model="searchCustomer" placeholder="Search customer by name or phone..." class="w-full px-3 py-2 border border-slate-200 rounded-md text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                            </div>
                            <div class="max-h-60 overflow-y-auto p-1">
                                <div @click="selectedCustomerId = ''; open = false" class="px-3 py-2 hover:bg-slate-50 cursor-pointer rounded text-sm text-slate-700 font-semibold border-b border-slate-50">
                                    Walk-in Customer
                                </div>
                                <template x-for="customer in filteredCustomers(searchCustomer)" :key="customer.id">
                                    <div @click="selectedCustomerId = customer.id; open = false" class="flex justify-between items-center px-3 py-2 hover:bg-slate-50 cursor-pointer rounded border-b border-slate-50">
                                        <div>
                                            <span class="block text-sm font-bold text-slate-850" x-text="customer.name"></span>
                                            <span class="block text-xs text-slate-500" x-text="customer.phone || 'No phone'"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="block text-[10px] uppercase text-slate-400 font-bold">Due Balance</span>
                                            <span class="block text-sm font-black text-red-600" x-text="'Rs ' + formatCurrency(customer.due_amount)"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="filteredCustomers(searchCustomer).length === 0" class="p-4 text-center text-sm text-slate-500">No matching customers found.</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" @click="sourceType = 'invoice'" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-xs transition whitespace-nowrap">Back to Invoices</button>
                </div>
                <!-- STEP 2: RETURN ITEMS -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6" x-show="(sourceType === 'invoice' && selectedSale) || sourceType === 'old'">
                    <h3 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Step 2: Items to Return</h3>
                    
                    <!-- If Invoice -->
                    <div x-show="sourceType === 'invoice'">
                        <table class="mobile-record-table w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="p-3 border-b border-slate-200">Product</th>
                                    <th class="p-3 border-b border-slate-200 text-right">Sold Qty</th>
                                    <th class="p-3 border-b border-slate-200 text-right">Unit Price</th>
                                    <th class="p-3 border-b border-slate-200">Condition</th>
                                    <th class="p-3 border-b border-slate-200 text-right">Return Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template x-for="(item, index) in returnItems" :key="item.id">
                                    <tr>
                                        <td data-label="Product" class="p-3 font-semibold text-slate-800">
                                            <span x-text="item.name"></span>
                                            <input type="hidden" :name="'items['+index+'][sale_item_id]'" :value="item.sale_item_id">
                                            <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                        </td>
                                        <td data-label="Sold Qty" class="p-3 text-right" x-text="item.max_qty"></td>
                                        <td data-label="Unit Price" class="p-3 text-right font-mono" x-text="formatCurrency(item.unit_price)"></td>
                                        <td data-label="Condition" class="p-3">
                                            <select x-model="item.stock_action" :name="'items['+index+'][stock_action]'" class="w-36 px-2 py-1.5 border border-slate-200 rounded text-xs bg-white">
                                                <option value="return_to_stock">Return to Stock</option>
                                                <option value="scrap">Scrap (Damaged)</option>
                                            </select>
                                        </td>
                                        <td data-label="Return Qty" class="p-3 text-right">
                                            <input type="number" step="any" min="0" :max="item.max_qty" x-model="item.qty" :name="'items['+index+'][quantity]'" class="w-20 px-2 py-1.5 border border-slate-200 rounded text-right text-sm">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- If Old Return -->
                    <div x-show="sourceType === 'old'">
                        <div class="flex gap-2 mb-4">
                            <select x-model="oldReturnProductSelect" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                                <option value="">Select product to return...</option>
                                <template x-for="product in products" :key="product.id">
                                    <option :value="product.id" x-text="product.name + ' - Current Cost: Rs. ' + product.cost_price"></option>
                                </template>
                            </select>
                            <button type="button" @click="addOldReturnProduct()" class="px-4 py-2 bg-blue-100 text-blue-700 font-bold rounded-lg text-xs hover:bg-blue-200">Add Item</button>
                        </div>
                        <table class="mobile-record-table w-full text-left border-collapse" x-show="returnItems.length > 0">
                            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="p-3 border-b border-slate-200">Product</th>
                                    <th class="p-3 border-b border-slate-200 text-right">Expense/Unit Value</th>
                                    <th class="p-3 border-b border-slate-200">Condition</th>
                                    <th class="p-3 border-b border-slate-200 text-right">Return Qty</th>
                                    <th class="p-3 border-b border-slate-200 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template x-for="(item, index) in returnItems" :key="item.uid">
                                    <tr>
                                        <td data-label="Product" class="p-3 font-semibold text-slate-800">
                                            <span x-text="item.name"></span>
                                            <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                            <input type="hidden" :name="'items['+index+'][unit_price]'" :value="item.unit_price">
                                        </td>
                                        <td data-label="Expense/Unit Value" class="p-3 text-right font-mono" x-text="formatCurrency(item.unit_price)"></td>
                                        <td data-label="Condition" class="p-3">
                                            <select x-model="item.stock_action" :name="'items['+index+'][stock_action]'" class="w-36 px-2 py-1.5 border border-slate-200 rounded text-xs bg-white">
                                                <option value="return_to_stock">Return to Stock</option>
                                                <option value="scrap">Scrap (Damaged)</option>
                                            </select>
                                        </td>
                                        <td data-label="Return Qty" class="p-3 text-right">
                                            <input type="number" step="any" min="0" x-model="item.qty" :name="'items['+index+'][quantity]'" class="w-20 px-2 py-1.5 border border-slate-200 rounded text-right text-sm">
                                        </td>
                                        <td data-label="Action" class="p-3 text-center">
                                            <button type="button" @click="removeReturnItem(index)" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-right">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-2">Return Value:</span>
                        <span class="text-xl font-black text-slate-800" x-text="'Rs. ' + formatCurrency(returnTotal)"></span>
                    </div>
                </div>

                <!-- STEP 3: RETURN TYPE -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6" x-show="returnTotal > 0">
                    <h3 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Step 3: Action</h3>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col p-4 border rounded-xl cursor-pointer transition text-center" :class="returnType === 'refund' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" x-model="returnType" value="refund" class="hidden">
                            <span class="text-2xl mb-2">💵</span>
                            <span class="font-bold text-slate-800">Refund</span>
                            <span class="text-xs text-slate-500 mt-1">Return item and give money/credit back</span>
                        </label>
                        <label class="flex flex-col p-4 border rounded-xl cursor-pointer transition text-center" :class="returnType === 'replacement' ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" x-model="returnType" value="replacement" class="hidden">
                            <span class="text-2xl mb-2">🔄</span>
                            <span class="font-bold text-slate-800">Replacement</span>
                            <span class="text-xs text-slate-500 mt-1">Exchange for exact same product(s)</span>
                        </label>
                        <label class="flex flex-col p-4 border rounded-xl cursor-pointer transition text-center" :class="returnType === 'exchange' ? 'border-purple-500 bg-purple-50' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" x-model="returnType" value="exchange" class="hidden">
                            <span class="text-2xl mb-2">🛒</span>
                            <span class="font-bold text-slate-800">Exchange (Different)</span>
                            <span class="text-xs text-slate-500 mt-1">Customer takes different items</span>
                        </label>
                    </div>
                </div>

                <!-- STEP 4: EXCHANGE PRODUCTS (Only if Exchange) -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6" x-show="returnType === 'exchange'">
                    <h3 class="font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Step 4: Select New Products</h3>
                    
                    <div class="flex gap-2 mb-4">
                        <select x-model="exchangeProductSelect" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                            <option value="">Select product...</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.name + ' - Rs. ' + product.retail_price"></option>
                            </template>
                        </select>
                        <button type="button" @click="addExchangeProduct()" class="px-4 py-2 bg-purple-100 text-purple-700 font-bold rounded-lg text-xs hover:bg-purple-200">Add Item</button>
                    </div>

                    <table class="mobile-record-table w-full text-left border-collapse" x-show="exchangeItems.length > 0">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="p-3 border-b border-slate-200">New Product</th>
                                <th class="p-3 border-b border-slate-200 text-right">Price</th>
                                <th class="p-3 border-b border-slate-200 text-right">Qty</th>
                                <th class="p-3 border-b border-slate-200 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <template x-for="(item, index) in exchangeItems" :key="item.uid">
                                <tr>
                                    <td data-label="New Product" class="p-3 font-semibold text-slate-800">
                                        <span x-text="item.name"></span>
                                        <input type="hidden" :name="'exchange_items['+index+'][product_id]'" :value="item.product_id">
                                    </td>
                                    <td data-label="Price" class="p-3 text-right">
                                        <input type="number" step="any" min="0" x-model="item.unit_price" :name="'exchange_items['+index+'][unit_price]'" class="w-24 px-2 py-1.5 border border-slate-200 rounded text-right text-sm">
                                    </td>
                                    <td data-label="Qty" class="p-3 text-right">
                                        <input type="number" step="any" min="1" x-model="item.qty" :name="'exchange_items['+index+'][quantity]'" class="w-20 px-2 py-1.5 border border-slate-200 rounded text-right text-sm">
                                    </td>
                                    <td data-label="Action" class="p-3 text-center">
                                        <button type="button" @click="removeExchangeItem(index)" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="mt-4 text-right">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-2">Exchange Total:</span>
                        <span class="text-xl font-black text-purple-700" x-text="'Rs. ' + formatCurrency(exchangeTotal)"></span>
                    </div>
                </div>

                <!-- STEP 5: FINANCIAL SETTLEMENT -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6" x-show="returnTotal > 0">
                    <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-2">
                        <h3 class="font-bold text-slate-800">Step 5: Financial Settlement</h3>
                        <div x-show="customerDueAmount() > 0" class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1.5 rounded-lg border border-red-100" x-transition>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Customer Due Balance:</span>
                            <span class="text-sm font-black" x-text="'Rs. ' + formatCurrency(customerDueAmount())"></span>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-8 items-center bg-slate-50 p-6 rounded-xl border border-slate-200 mb-6">
                        <div class="flex-1 text-center border-r border-slate-200 px-4">
                            <div class="text-xs font-bold text-slate-500 uppercase">Return Value</div>
                            <div class="text-2xl font-black text-slate-800 mt-1" x-text="'- Rs. ' + formatCurrency(returnTotal)"></div>
                        </div>
                        <div class="flex-1 text-center border-r border-slate-200 px-4" x-show="returnType === 'exchange'">
                            <div class="text-xs font-bold text-slate-500 uppercase">New Items Value</div>
                            <div class="text-2xl font-black text-purple-700 mt-1" x-text="'+ Rs. ' + formatCurrency(exchangeTotal)"></div>
                        </div>
                        <div class="flex-1 text-center px-4">
                            <div class="text-xs font-bold text-slate-500 uppercase">Net Difference</div>
                            <div class="text-3xl font-black mt-1" :class="netDifference > 0 ? 'text-red-600' : (netDifference < 0 ? 'text-emerald-600' : 'text-slate-800')" x-text="netDifference > 0 ? 'To Pay: Rs. ' + formatCurrency(netDifference) : 'To Refund: Rs. ' + formatCurrency(Math.abs(netDifference))"></div>
                        </div>
                    </div>

                    <!-- Options based on net difference -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Shop Owes Customer (Negative Difference) -->
                        <div x-show="netDifference <= 0" class="space-y-4" x-effect="refundDueAmount = Math.min(Math.abs(netDifference), customerDueAmount())">
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                                <div class="flex justify-between items-center text-sm font-bold border-b border-slate-200 pb-2 mb-2">
                                    <span class="text-slate-600">Total Refund Required:</span>
                                    <span class="text-slate-900" x-text="'Rs. ' + formatCurrency(Math.abs(netDifference))"></span>
                                </div>
                                
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 flex justify-between">
                                        <span>Deduct from Due Balance</span>
                                        <span class="text-red-500" x-text="'Max: Rs. ' + formatCurrency(Math.min(Math.abs(netDifference), customerDueAmount()))"></span>
                                    </label>
                                    <input type="number" step="0.01" min="0" :max="Math.min(Math.abs(netDifference), customerDueAmount())" x-model.number="refundDueAmount" name="refund_due_amount" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white font-bold text-slate-800 focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 flex justify-between">
                                        <span>Cash / Bank Refund Remainder</span>
                                        <span class="text-emerald-600" x-text="'Rs. ' + formatCurrency(Math.max(0, Math.abs(netDifference) - (refundDueAmount || 0)))"></span>
                                    </label>
                                    <input type="hidden" name="refund_cash_amount" :value="Math.max(0, Math.abs(netDifference) - (refundDueAmount || 0))">
                                    <div class="flex gap-2">
                                        <select name="refund_method" class="w-1/2 px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white font-semibold focus:ring-2 focus:ring-purple-500">
                                            <option value="Cash">Cash Handed Over</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="No Refund">Keep as Shop Profit / Ignore</option>
                                        </select>
                                        <div class="w-1/2 px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-slate-100 font-bold text-slate-800 flex items-center justify-end" x-text="formatCurrency(Math.max(0, Math.abs(netDifference) - (refundDueAmount || 0)))"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Owes Shop (Positive Difference) -->
                        <div x-show="netDifference > 0" class="space-y-4">
                            <div>
                                <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 block">Payment Received Via</label>
                                <select name="payment_method" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white font-semibold">
                                    <option value="Cash">Cash Received</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Card">Card</option>
                                    <option value="Due">Add to Customer Due</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 block">Amount Paid Now</label>
                                <input type="number" step="any" min="0" name="payment_amount" :value="netDifference" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-4">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 block">Return Date</label>
                                    <input type="date" name="return_date" value="{{ now()->toDateString() }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white" required>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500 mb-1.5 block">Reason / Notes</label>
                                    <input type="text" name="reason" placeholder="e.g. Changed mind, damaged..." class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-200 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-slate-850 hover:bg-slate-950 text-white rounded-lg font-bold text-sm shadow-md transition transform hover:-translate-y-0.5" x-text="netDifference > 0 ? 'Process Exchange & Sale' : 'Process Return & Refund'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('returnProcess', () => ({
                search: '',
                sales: @json($sales),
                customers: @json($customers),
                products: @json($products),
                
                sourceType: '{{ $selectedSale ? "invoice" : "invoice" }}',
                selectedSale: @json($selectedSale),
                selectedCustomerId: '{{ $selectedSale ? $selectedSale->customer_id : "" }}',
                
                returnItems: [],
                oldReturnProductSelect: '',
                
                returnType: 'refund',
                refundDueAmount: 0,
                
                exchangeItems: [],
                exchangeProductSelect: '',
                
                init() {
                    if (this.selectedSale) {
                        this.populateInvoiceItems();
                    }
                    this.$watch('returnType', value => {
                        if (value === 'replacement') {
                            this.setupReplacement();
                        } else if (value === 'refund') {
                            this.exchangeItems = [];
                        }
                    });
                },
                
                get filteredSales() {
                    let term = (this.search || '').toLowerCase();
                    if (!term) return this.sales;
                    return this.sales.filter(s => 
                        s.invoice_no.toLowerCase().includes(term) || 
                        (s.customer && s.customer.name.toLowerCase().includes(term)) ||
                        (s.customer && s.customer.phone && s.customer.phone.toLowerCase().includes(term))
                    );
                },
                
                filteredCustomers(term) {
                    if (!term) return this.customers;
                    let lowerTerm = term.toLowerCase();
                    return this.customers.filter(c => 
                        c.name.toLowerCase().includes(lowerTerm) || 
                        (c.phone && c.phone.toLowerCase().includes(lowerTerm))
                    );
                },
                
                selectedCustomerName() {
                    if (!this.selectedCustomerId) return 'Walk-in Customer';
                    let c = this.customers.find(c => c.id == this.selectedCustomerId);
                    return c ? c.name + (c.phone ? ' ('+c.phone+')' : '') : 'Walk-in Customer';
                },
                
                customerDueAmount() {
                    if (!this.selectedCustomerId) return 0;
                    let c = this.customers.find(c => c.id == this.selectedCustomerId);
                    return c ? parseFloat(c.due_amount || 0) : 0;
                },
                
                loadSale(id) {
                    window.location.href = '?sale_id=' + id;
                },
                
                resetState() {
                    this.returnItems = [];
                    this.exchangeItems = [];
                    this.returnType = 'refund';
                },
                
                populateInvoiceItems() {
                    this.returnItems = this.selectedSale.items.map(item => {
                        let returned = item.returned_items ? item.returned_items.reduce((sum, r) => sum + parseFloat(r.quantity), 0) : 0;
                        let maxQty = parseFloat(item.quantity) - returned;
                        return {
                            id: item.id,
                            sale_item_id: item.id,
                            product_id: item.product_id,
                            name: item.product ? item.product.name : 'Unknown',
                            max_qty: maxQty > 0 ? maxQty : 0,
                            qty: 0,
                            unit_price: parseFloat(item.unit_price || item.selling_price),
                            stock_action: 'return_to_stock'
                        };
                    });
                },
                
                addOldReturnProduct() {
                    if (!this.oldReturnProductSelect) return;
                    let prod = this.products.find(p => p.id == this.oldReturnProductSelect);
                    if (prod) {
                        this.returnItems.push({
                            uid: Date.now(),
                            product_id: prod.id,
                            name: prod.name,
                            qty: 1,
                            unit_price: parseFloat(prod.cost_price || 0),
                            stock_action: 'return_to_stock'
                        });
                        this.oldReturnProductSelect = '';
                    }
                },
                
                removeReturnItem(index) {
                    this.returnItems.splice(index, 1);
                },
                
                addExchangeProduct() {
                    if (!this.exchangeProductSelect) return;
                    let prod = this.products.find(p => p.id == this.exchangeProductSelect);
                    if (prod) {
                        this.exchangeItems.push({
                            uid: Date.now(),
                            product_id: prod.id,
                            name: prod.name,
                            qty: 1,
                            unit_price: parseFloat(prod.retail_price || 0)
                        });
                        this.exchangeProductSelect = '';
                    }
                },
                
                removeExchangeItem(index) {
                    this.exchangeItems.splice(index, 1);
                },
                
                setupReplacement() {
                    this.exchangeItems = [];
                    this.returnItems.forEach(item => {
                        if (parseFloat(item.qty) > 0) {
                            this.exchangeItems.push({
                                uid: Date.now() + Math.random(),
                                product_id: item.product_id,
                                name: item.name,
                                qty: item.qty,
                                unit_price: item.unit_price
                            });
                        }
                    });
                },
                
                get returnTotal() {
                    return this.returnItems.reduce((sum, item) => sum + (parseFloat(item.qty || 0) * parseFloat(item.unit_price || 0)), 0);
                },
                
                get exchangeTotal() {
                    if (this.returnType === 'refund') return 0;
                    return this.exchangeItems.reduce((sum, item) => sum + (parseFloat(item.qty || 0) * parseFloat(item.unit_price || 0)), 0);
                },
                
                get netDifference() {
                    return this.exchangeTotal - this.returnTotal;
                },
                
                formatCurrency(val) {
                    return Number(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            }));
        });
    </script>
</x-layouts::app>
