<x-layouts::app title="Period Details: {{ $period->period_name }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">{{ $period->period_name }}</h1>
            <p class="text-sm text-black">{{ $period->start_date->format('M d, Y') }} - {{ $period->end_date->format('M d, Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tax.periods.index') }}" class="px-4 py-2 border border-slate-300 text-black rounded-lg hover:bg-slate-50 text-sm font-medium transition-colors">
                Back to List
            </a>
            @if($period->period_status === 'Open')
                <!-- Close Button placeholder -->
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium transition-colors">
                    Close Period
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-black text-xs font-semibold uppercase tracking-wider mb-2">Opening Payable</h3>
            <p class="text-xl font-bold text-black">Rs. {{ number_format($period->opening_payable_balance, 2) }}</p>
        </div>
        
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-black text-xs font-semibold uppercase tracking-wider mb-2">Net Output - Input VAT</h3>
            <p class="text-xl font-bold text-black">Rs. {{ number_format($period->output_tax - $period->input_tax - $period->import_tax, 2) }}</p>
        </div>
        
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-black text-xs font-semibold uppercase tracking-wider mb-2">Total Adjustments</h3>
            <p class="text-xl font-bold text-black">Rs. {{ number_format($period->adjustments, 2) }}</p>
        </div>
        
        <div class="bg-white rounded-xl border-blue-200 p-5 shadow-sm bg-blue-50/50">
            <h3 class="text-blue-600 text-xs font-semibold uppercase tracking-wider mb-2">Closing Payable Balance</h3>
            <p class="text-2xl font-bold text-blue-700">Rs. {{ number_format($period->closing_payable_balance, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center bg-slate-50">
            <h2 class="font-semibold text-black">Daily VAT Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-100 border-b border-slate-200 text-black">
                    <tr>
                        <th class="px-6 py-3 font-medium">Date</th>
                        <th class="px-6 py-3 font-medium text-center">Bills (Out/In)</th>
                        <th class="px-6 py-3 font-medium text-right">Taxable Sales</th>
                        <th class="px-6 py-3 font-medium text-right">Output VAT</th>
                        <th class="px-6 py-3 font-medium text-right">Taxable Purchases</th>
                        <th class="px-6 py-3 font-medium text-right">Input VAT</th>
                        <th class="px-6 py-3 font-medium text-right">Import VAT</th>
                        <th class="px-6 py-3 font-medium text-right">Net Daily VAT</th>
                        <th class="px-6 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-black">
                    @forelse($dailySummaries as $day)
                    @php
                        $netDaily = $day['output_vat'] - $day['input_vat'] - $day['import_vat'];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3 font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                        <td class="px-6 py-3 text-center">
                            <span class="text-blue-600">{{ $day['sales_count'] }}</span> / 
                            <span class="text-orange-600">{{ $day['purchases_count'] }}</span>
                        </td>
                        <td class="px-6 py-3 text-right">Rs. {{ number_format($day['taxable_sales'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-medium text-blue-600">Rs. {{ number_format($day['output_vat'], 2) }}</td>
                        <td class="px-6 py-3 text-right">Rs. {{ number_format($day['taxable_purchases'], 2) }}</td>
                        <td class="px-6 py-3 text-right text-orange-600">Rs. {{ number_format($day['input_vat'], 2) }}</td>
                        <td class="px-6 py-3 text-right text-red-600">Rs. {{ number_format($day['import_vat'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold {{ $netDaily >= 0 ? 'text-black' : 'text-green-600' }}">
                            Rs. {{ number_format($netDaily, 2) }}
                        </td>
                        <td class="px-6 py-3 text-center">
                            <button onclick="viewDailyDetails('{{ $day['date'] }}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Bills</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-black">
                            No tax transactions recorded in this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-black">Totals:</td>
                        <td class="px-6 py-3 text-right text-blue-700">Rs. {{ number_format($period->output_tax, 2) }}</td>
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3 text-right text-orange-700">Rs. {{ number_format($period->input_tax, 2) }}</td>
                        <td class="px-6 py-3 text-right text-red-700">Rs. {{ number_format($period->import_tax, 2) }}</td>
                        <td class="px-6 py-3 text-right text-black">Rs. {{ number_format($period->output_tax - $period->input_tax - $period->import_tax, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Daily Details Modal -->
    <div id="dailyDetailsModal" class="fixed inset-0 z-[100] hidden bg-slate-900/50 backdrop-blur-sm transition-opacity" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative w-full max-w-5xl transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <h3 class="text-lg font-semibold text-black" id="modal-title">Transactions for <span id="modalDateTitle"></span></h3>
                    <button onclick="closeDailyDetails()" class="text-black hover:text-black focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 h-[60vh] overflow-y-auto" id="modalContent">
                    <div class="flex justify-center items-center h-32">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </div>
                
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end">
                    <button onclick="closeDailyDetails()" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-black shadow-sm border border-slate-300 hover:bg-slate-50">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function viewDailyDetails(date) {
            document.getElementById('modalDateTitle').innerText = date;
            const modal = document.getElementById('dailyDetailsModal');
            modal.classList.remove('hidden');
            
            // Fetch daily transactions via AJAX
            fetch(`/tax/transactions?date=${date}&period_id={{ $period->id }}&ajax=true`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('modalContent').innerHTML = '<div class="text-red-500 text-center p-4">Error loading transactions.</div>';
                });
        }
        
        function closeDailyDetails() {
            document.getElementById('dailyDetailsModal').classList.add('hidden');
        }
    </script>
    @endpush
</x-layouts::app>
