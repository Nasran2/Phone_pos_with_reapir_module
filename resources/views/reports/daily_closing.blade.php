<x-report-layout 
    title="Daily Closing Report" 
    :summary="$summary ?? []" 
    :chartData="$chartData ?? []" 
    :exportPdf="false" 
    :exportExcel="false" 
    :exportRoute="route('reports.daily_closing')" 
    :resetRoute="route('reports.daily_closing')"
>
    <x-slot name="filterContent">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-black mb-1">Select Date</label>
                <input type="date" name="date" value="{{ request('date', $date) }}" class="w-full rounded-lg border-slate-900 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
        </div>
    </x-slot>

    <div class="p-6">
        <form action="{{ route('reports.daily_closing.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Cash In -->
                <div>
                    <h3 class="text-lg font-bold text-emerald-700 border-b border-emerald-100 pb-2 mb-4">Cash In</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-slate-100">
                            <span class="text-black font-medium">Opening Cash</span>
                            <input type="number" step="any" name="opening_cash" value="{{ old('opening_cash', $openingCash) }}" class="w-32 text-right rounded-md border-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-black font-medium">{{ \App\Support\Features::wholesaleEnabled() ? 'Retail Sales' : 'Sales Receipts' }}</span>
                            <span class="font-bold text-black">Rs. {{ number_format(\App\Support\Features::wholesaleEnabled() ? $retailSales : $salesReceipts, 2) }}</span>
                            <input type="hidden" name="retail_sales" value="{{ $retailSales }}">
                        </div>
                        @if(\App\Support\Features::wholesaleEnabled())
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-black font-medium">Wholesale Sales</span>
                            <span class="font-bold text-black">Rs. {{ number_format($wholesaleSales, 2) }}</span>
                            <input type="hidden" name="wholesale_sales" value="{{ $wholesaleSales }}">
                        </div>
                        @endif
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-black font-medium">Customer Due Collections</span>
                            <span class="font-bold text-black">Rs. {{ number_format($customerCollections, 2) }}</span>
                            <input type="hidden" name="customer_due_collections" value="{{ $customerCollections }}">
                        </div>
                        <div class="flex justify-between py-3 border-t-2 border-emerald-200 mt-2 bg-emerald-50 px-2 rounded-lg">
                            <span class="text-emerald-800 font-bold">Total Cash In</span>
                            <span class="font-bold text-emerald-800 text-lg">Rs. {{ number_format($cashIn, 2) }}</span>
                            <input type="hidden" name="cash_in" value="{{ $cashIn }}">
                        </div>
                    </div>
                </div>

                <!-- Cash Out -->
                <div>
                    <h3 class="text-lg font-bold text-rose-700 border-b border-rose-100 pb-2 mb-4">Cash Out</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-black font-medium">Supplier Payments</span>
                            <span class="font-bold text-black">Rs. {{ number_format($supplierPayments, 2) }}</span>
                            <input type="hidden" name="supplier_payments" value="{{ $supplierPayments }}">
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100">
                            <span class="text-black font-medium">Expenses</span>
                            <span class="font-bold text-black">Rs. {{ number_format($expenses, 2) }}</span>
                            <input type="hidden" name="expenses" value="{{ $expenses }}">
                        </div>
                        <div class="flex justify-between py-3 border-t-2 border-rose-200 mt-2 bg-rose-50 px-2 rounded-lg">
                            <span class="text-rose-800 font-bold">Total Cash Out</span>
                            <span class="font-bold text-rose-800 text-lg">Rs. {{ number_format($cashOut, 2) }}</span>
                            <input type="hidden" name="cash_out" value="{{ $cashOut }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Closing Calculation -->
            <div class="mt-8 bg-slate-50 p-6 rounded-xl border border-slate-200 shadow-inner">
                <h3 class="text-xl font-extrabold text-black mb-6 border-b border-slate-200 pb-3">Reconciliation</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                    <div>
                        <label class="block text-sm font-bold text-black mb-1">Expected Cash Balance</label>
                        <div class="text-2xl font-black text-blue-600">Rs. {{ number_format($expectedCash, 2) }}</div>
                        <input type="hidden" name="expected_cash_balance" id="expected_cash_balance" value="{{ $expectedCash }}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-black mb-1">Actual Cash Counted *</label>
                        <input type="number" step="any" name="actual_cash_counted" id="actual_cash_counted" required class="w-full text-xl font-bold py-3 text-right rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-black mb-1">Difference</label>
                        <div id="difference_display" class="text-2xl font-black text-black">Rs. 0.00</div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-bold text-black mb-1">Closing Note / Remarks</label>
                    <textarea name="closing_note" rows="2" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Any explanations for cash differences..."></textarea>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition text-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Save Daily Closing
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const expectedInput = document.getElementById('expected_cash_balance');
            const actualInput = document.getElementById('actual_cash_counted');
            const diffDisplay = document.getElementById('difference_display');

            actualInput.addEventListener('input', function() {
                const expected = parseFloat(expectedInput.value) || 0;
                const actual = parseFloat(actualInput.value) || 0;
                const diff = actual - expected;
                
                diffDisplay.textContent = 'Rs. ' + diff.toFixed(2);
                
                if (diff < 0) {
                    diffDisplay.className = 'text-2xl font-black text-rose-600';
                } else if (diff > 0) {
                    diffDisplay.className = 'text-2xl font-black text-emerald-600';
                } else {
                    diffDisplay.className = 'text-2xl font-black text-black';
                }
            });
        });
    </script>
</x-report-layout>
