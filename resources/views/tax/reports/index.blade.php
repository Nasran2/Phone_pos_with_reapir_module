<x-layouts::app title="Tax Reports">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Tax Reports</h1>
            <p class="text-sm text-black">Generate and export comprehensive tax reports.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v1a3 3 0 106 0v-1m-6 0a3 3 0 006 0m-6 0V9a3 3 0 013-3v0a3 3 0 013 3v8M5 21h14M5 21a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5z"></path>
                </svg>
            </div>
            <h3 class="font-semibold text-black text-lg mb-2">VAT Return Summary</h3>
            <p class="text-sm text-black mb-4">Detailed breakdown of Output VAT, Input VAT, and Import Tax for filing periods.</p>
            <a href="{{ route('tax.reports.vat') }}" class="inline-block text-sm font-medium text-blue-600 hover:text-blue-800">Generate Report &rarr;</a>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <h3 class="font-semibold text-black text-lg mb-2">Payment Register</h3>
            <p class="text-sm text-black mb-4">A complete ledger of all approved payments and their associated tax periods.</p>
            <a href="{{ route('tax.reports.payments') }}" class="inline-block text-sm font-medium text-blue-600 hover:text-blue-800">Generate Report &rarr;</a>
        </div>
    </div>
</x-layouts::app>
