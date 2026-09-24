<x-report-layout 
    title="Supplier Due Report" 
    :summary="$summary ?? []" 
    :chartData="$chartData ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.supplier_due')" 
    :resetRoute="route('reports.supplier_due')"
>
    <x-slot name="filterContent"><p class="text-sm text-slate-500">Current balances and stock. Historical date filters do not apply.</p></x-slot>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Supplier</th>
                <th class="p-4 font-bold">Phone</th>
                <th class="p-4 font-bold">Payable Balance</th>

            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            @forelse($suppliers ?? [] as $item)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4">{{ $item->name }}</td>
                    <td class="p-4">{{ $item->phone }}</td>
                    <td class="p-4">{{ $item->balance }}</td>

                </tr>
            @empty
                <tr>
                    <td colspan="3" class="p-8 text-center text-slate-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="font-medium text-slate-600">No records found for the selected filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(isset($suppliers) && method_exists($suppliers, 'hasPages') && $suppliers->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $suppliers->links() }}
        </div>
    @endif
</x-report-layout>