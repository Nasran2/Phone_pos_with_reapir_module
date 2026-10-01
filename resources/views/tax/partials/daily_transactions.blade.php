@if($transactions->isEmpty())
    <div class="p-8 text-center text-black">
        No transactions found for this date.
    </div>
@else
    <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-slate-100 border-b border-slate-200 text-black sticky top-0">
            <tr>
                <th class="px-4 py-2 font-medium">Type</th>
                <th class="px-4 py-2 font-medium">Reference #</th>
                <th class="px-4 py-2 font-medium text-right">Taxable Amt</th>
                <th class="px-4 py-2 font-medium text-right">Tax Amt</th>
                <th class="px-4 py-2 font-medium text-right">Recoverable</th>
                <th class="px-4 py-2 font-medium">Tax Type</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-black">
            @foreach($transactions as $txn)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-2 font-medium">
                    @if($txn->input_or_output === 'output')
                        <span class="text-blue-600">Sales (Output)</span>
                    @else
                        <span class="text-orange-600">Purchase (Input)</span>
                    @endif
                </td>
                <td class="px-4 py-2 font-medium text-black">{{ class_basename($txn->reference_type) }} #{{ $txn->reference_id }}</td>
                <td class="px-4 py-2 text-right">Rs. {{ number_format($txn->taxable_amount, 2) }}</td>
                <td class="px-4 py-2 text-right font-medium {{ $txn->input_or_output === 'output' ? 'text-blue-600' : 'text-black' }}">Rs. {{ number_format($txn->tax_amount, 2) }}</td>
                <td class="px-4 py-2 text-right font-medium {{ $txn->input_or_output === 'input' ? 'text-orange-600' : 'text-black' }}">Rs. {{ number_format($txn->recoverable_amount, 2) }}</td>
                <td class="px-4 py-2">
                    <span class="px-2 py-1 rounded bg-slate-100 text-xs font-semibold">{{ $txn->tax_type }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif
