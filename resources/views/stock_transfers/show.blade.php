<x-layouts::app :title="__('Stock Transfer Details')">
    <!-- SweetAlert2 CDN for modern popup alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock-transfers.index') }}" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Transfer {{ $stockTransfer->reference_no }}</h2>
            @if($stockTransfer->status === 'completed')
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded text-[10px] uppercase tracking-wider ml-2 border border-emerald-200">Completed</span>
            @else
                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded text-[10px] uppercase tracking-wider ml-2 border border-amber-200">Pending</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if($stockTransfer->status === 'pending' && auth()->user()->hasPermission('stock_transfers.approve'))
            <button onclick="confirmComplete({{ $stockTransfer->id }}, '{{ $stockTransfer->reference_no }}')" class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                <span>Complete Transfer</span>
            </button>
            <form id="complete-form-{{ $stockTransfer->id }}" method="POST" action="{{ route('stock-transfers.complete', $stockTransfer->id) }}" class="hidden">
                @csrf
                @method('PATCH')
            </form>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium max-w-6xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
        </div>
        @endif
        
        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium max-w-6xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800">✕</button>
        </div>
        @endif

        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Items Table -->
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800">Transfer Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-500 text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-6 py-3">#</th>
                                    <th class="px-6 py-3">Product Name</th>
                                    <th class="px-6 py-3 text-right">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                                @foreach($stockTransfer->items as $index => $item)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-3 text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-slate-800">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ $item->product->sku ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-800">
                                        {{ (float) $item->quantity }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200/60 font-bold text-xs">
                                <tr>
                                    <td colspan="2" class="px-6 py-3 text-right text-slate-600">Total Quantity:</td>
                                    <td class="px-6 py-3 text-right text-slate-800">{{ (float) $stockTransfer->items->sum('quantity') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800">Transfer Information</h3>
                    </div>
                    <div class="p-6 space-y-4 text-sm">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Transfer Date</div>
                            <div class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($stockTransfer->transfer_date)->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Source Store</div>
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $stockTransfer->fromStore->name ?? 'Unknown' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Destination Store</div>
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $stockTransfer->toStore->name ?? 'Unknown' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Created By</div>
                            <div class="font-medium text-slate-800">{{ $stockTransfer->creator->name ?? 'System' }}</div>
                        </div>
                        
                        @if($stockTransfer->notes)
                        <div class="pt-4 border-t border-slate-100">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Notes</div>
                            <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200/60">{{ $stockTransfer->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function confirmComplete(id, ref) {
            Swal.fire({
                title: 'Complete Transfer?',
                text: `Are you sure you want to complete transfer ${ref}? Stock will be moved permanently.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Complete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`complete-form-${id}`).submit();
                }
            });
        }
    </script>
</x-layouts::app>
