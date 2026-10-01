<x-layouts::app title="VAT Transactions">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">VAT Transactions</h1>
            <p class="text-sm text-black">Log of all Input and Output VAT movements.</p>
        </div>
        <div class="flex gap-2">
            <!-- Filter form could go here in the future -->
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-black">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium text-right">Taxable Amt</th>
                        <th class="px-4 py-3 font-medium text-right">Tax Amt</th>
                        <th class="px-4 py-3 font-medium text-right">Recoverable (Input)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-black">
                    @forelse($transactions as $txn)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">
                            @if($txn->input_or_output === 'output')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold uppercase tracking-wider">Output VAT</span>
                            @else
                                <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-semibold uppercase tracking-wider">Input VAT</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-black">{{ class_basename($txn->reference_type) }} #{{ $txn->reference_id }}</td>
                        <td class="px-4 py-3 text-right">Rs. {{ number_format($txn->taxable_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $txn->input_or_output === 'output' ? 'text-blue-600 font-medium' : '' }}">
                            Rs. {{ number_format($txn->tax_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right {{ $txn->input_or_output === 'input' ? 'text-orange-600 font-medium' : 'text-black' }}">
                            Rs. {{ number_format($txn->recoverable_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-black">
                            No VAT transactions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
