<x-report-layout 
    title="Cash & Bank Report" 
    :summary="$summary ?? []" 
    :chartData="$chartData ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.cash_bank')" 
    :resetRoute="route('reports.cash_bank')"
>
    <x-slot name="filterContent"><p class="text-sm text-black">Current balances and stock. Historical date filters do not apply.</p></x-slot>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-black text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Date</th>
                <th class="p-4 font-bold">Account</th>
                <th class="p-4 font-bold">Type</th>
                <th class="p-4 font-bold">Amount</th>
                <th class="p-4 font-bold">Balance</th>

            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-black">
            @forelse($data ?? [] as $item)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4">{{ $item->created_at?->format('Y-m-d') ?? '-' }}</td>
                    <td class="p-4">{{ $item->account_name ?? '-' }}</td>
                    <td class="p-4">{{ $item->account_type ?? '-' }}</td>
                    <td class="p-4">Rs. {{ number_format((float) ($item->current_balance ?? 0), 2) }}</td>
                    <td class="p-4">{{ $item->status ? 'Active' : 'Inactive' }}</td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-black">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-black mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="font-medium text-black">No records found for the selected filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(isset($data) && method_exists($data, 'hasPages') && $data->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $data->links() }}
        </div>
    @endif
</x-report-layout>
