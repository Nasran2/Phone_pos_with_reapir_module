<x-layouts::app :title="__('Stock Transfers')">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Stock Transfers</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $transfers->total() }} Transfers
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('stock_transfers.create'))
            <a href="{{ route('stock-transfers.create') }}" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>New Transfer</span>
            </a>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium max-w-6xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">✕</button>
        </div>
        @endif

        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium max-w-6xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800">✕</button>
        </div>
        @endif

        <div class="max-w-6xl mx-auto space-y-4">
            <!-- Search & Filters -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-xs">
                <form method="GET" action="{{ route('stock-transfers.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Reference No..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-colors">
                    </div>
                    <div class="sm:w-40">
                        <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="from_store_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">From Store: All</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('from_store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="to_store_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">To Store: All</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('to_store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-sm whitespace-nowrap">
                            Filter
                        </button>
                        <a href="{{ route('stock-transfers.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-lg transition whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                                <th class="px-6 py-4">Reference No / Date</th>
                                <th class="px-6 py-4">From Store</th>
                                <th class="px-6 py-4">To Store</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Created By</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                            @forelse($transfers as $transfer)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    <a href="{{ route('stock-transfers.show', $transfer) }}" class="text-blue-600 hover:underline">
                                        {{ $transfer->reference_no }}
                                    </a>
                                    <div class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ \Carbon\Carbon::parse($transfer->transfer_date)->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-700">
                                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $transfer->fromStore->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-700">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $transfer->toStore->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($transfer->status === 'completed')
                                        <span class="px-2 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px] border border-emerald-200">Completed</span>
                                    @else
                                        <span class="px-2 py-1 bg-amber-50 text-amber-700 font-bold rounded-md text-[10px] border border-amber-200">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $transfer->creator->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (auth()->user()->hasPermission('stock_transfers.view'))
                                        <a href="{{ route('stock-transfers.show', $transfer) }}" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition rounded-lg" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        @endif
                                        
                                        @if($transfer->status === 'pending' && auth()->user()->hasPermission('stock_transfers.approve'))
                                        <button 
                                            onclick="confirmComplete({{ $transfer->id }}, '{{ $transfer->reference_no }}')"
                                            class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition rounded-lg"
                                            title="Mark as Completed"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                        <form id="complete-form-{{ $transfer->id }}" method="POST" action="{{ route('stock-transfers.complete', $transfer->id) }}" class="hidden">
                                            @csrf
                                            @method('PATCH')
                                        </form>
                                        @endif

                                        @if(auth()->user()->hasPermission('stock_transfers.delete') && $transfer->status === 'pending')
                                        <button 
                                            onclick="confirmDelete({{ $transfer->id }}, '{{ $transfer->reference_no }}')"
                                            class="p-1.5 text-slate-400 hover:text-red-650 hover:bg-red-50 transition rounded-lg"
                                            title="Delete Transfer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        <form id="delete-form-{{ $transfer->id }}" method="POST" action="{{ route('stock-transfers.destroy', $transfer->id) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <span class="font-bold">No stock transfers found.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transfers->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $transfers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Scripting -->
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

        function confirmDelete(id, ref) {
            Swal.fire({
                title: 'Delete Transfer?',
                text: `Are you sure you want to delete pending transfer ${ref}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            });
        }
    </script>
</x-layouts::app>
