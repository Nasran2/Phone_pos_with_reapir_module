<x-layouts::app title="Tax Adjustments">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Tax Adjustments</h1>
            <p class="text-sm text-slate-500">Record manual adjustments to tax liabilities.</p>
        </div>
        
        @if(auth()->user()->hasPermission('tax_adjustments.create'))
        <a href="{{ route('tax.adjustments.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
            + New Adjustment
        </a>
        @endif
    </div>

    <!-- Alert -->
    @if(session('success'))
    <div class="mb-4 bg-green-50 text-green-800 p-4 rounded-lg text-sm border border-green-200">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 text-red-800 p-4 rounded-lg text-sm border border-red-200">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Period</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium text-right">Amount</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                        <th class="px-4 py-3 font-medium">Reason</th>
                        <th class="px-4 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($adjustments as $adj)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-slate-900">{{ \Carbon\Carbon::parse($adj->adjustment_date)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ $adj->period->period_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $adj->adjustment_type }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $adj->amount >= 0 ? 'text-red-600' : 'text-green-600' }}">
                            Rs. {{ number_format(abs($adj->amount), 2) }}
                            @if($adj->amount >= 0)
                                <span class="text-[10px] text-red-500 ml-1">(Increase)</span>
                            @else
                                <span class="text-[10px] text-green-500 ml-1">(Decrease)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($adj->status === 'Approved')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-green-100 text-green-700 uppercase tracking-wider">Approved</span>
                            @elseif($adj->status === 'Draft')
                                <span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 uppercase tracking-wider">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate" title="{{ $adj->reason }}">
                            {{ Str::limit($adj->reason, 30) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($adj->status === 'Draft' && auth()->user()->hasPermission('tax_adjustments.approve'))
                                <form action="{{ route('tax.adjustments.approve', $adj) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">Approve</button>
                                </form>
                            @else
                                <span class="text-slate-400 text-sm">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                            No tax adjustments found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-slate-200">
            {{ $adjustments->links() }}
        </div>
    </div>
</x-layouts::app>
