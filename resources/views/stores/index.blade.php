<x-layouts::app :title="__('Store Management')">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black dark:text-white">Store Locations</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $stores->total() }} Stores
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('stores.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Store</span>
            </button>
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
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
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
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1 max-w-6xl mx-auto">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="max-w-6xl mx-auto space-y-4">
            <!-- Search Filter -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-xs">
                <form method="GET" action="{{ route('stores.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search stores..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-colors">
                    </div>
                    <div class="sm:w-48">
                        <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-sm cursor-pointer whitespace-nowrap">
                            Search
                        </button>
                        <a href="{{ route('stores.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-black text-xs font-bold rounded-lg transition cursor-pointer whitespace-nowrap">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Stores Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[11px] font-bold uppercase tracking-wider">
                                <th class="px-6 py-4">Store Name</th>
                                <th class="px-6 py-4">Code</th>
                                <th class="px-6 py-4">Contact & Manager</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-black text-xs">
                            @forelse($stores as $store)
                            <tr class="hover:bg-slate-50/50 transition {{ $store->status ? '' : 'bg-slate-50/30' }}">
                                <td class="px-6 py-4 font-bold text-black">
                                    <div class="flex items-center gap-2">
                                        {{ $store->name }}
                                        @if($store->is_default)
                                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-[10px] uppercase font-bold border border-indigo-200" title="Main Store">Default</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-black font-bold">
                                    {{ $store->code }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        @if($store->manager)
                                            <div class="flex items-center gap-1.5 text-black">
                                                <svg class="w-3.5 h-3.5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                <span>{{ $store->manager }}</span>
                                            </div>
                                        @endif
                                        @if($store->phone)
                                            <div class="flex items-center gap-1.5 text-black text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                <span>{{ $store->phone }}</span>
                                            </div>
                                        @endif
                                        @if($store->address)
                                            <div class="flex items-start gap-1.5 text-black text-[11px] mt-0.5">
                                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                <span class="truncate max-w-[200px]" title="{{ $store->address }}">{{ $store->address }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($store->status)
                                        <span class="px-2 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-md text-[10px] border border-emerald-200">Active</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-100 text-black font-bold rounded-md text-[10px] border border-slate-200">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(auth()->user()->hasPermission('stores.edit'))
                                        <button 
                                            onclick="openEditModal({{ json_encode($store) }})"
                                            class="p-1.5 text-black hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                            title="Edit Store"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        @endif
    
                                        @if(auth()->user()->hasPermission('stores.delete') && !$store->is_default)
                                        <button 
                                            onclick="confirmDelete({{ $store->id }}, '{{ addslashes($store->name) }}')"
                                            class="p-1.5 text-black hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                            title="Delete Store"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        <form id="delete-form-{{ $store->id }}" method="POST" action="{{ route('stores.destroy', $store->id) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-black">
                                    <svg class="w-10 h-10 mx-auto text-black mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="font-bold">No stores found.</span>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($stores->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $stores->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- STORE CREATE / EDIT MODAL -->
    <div id="storeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-black" id="modalTitle">Create Store</h3>
                <button onclick="closeModal()" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <form id="storeForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Store Name *</label>
                        <input type="text" name="name" id="store_name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="e.g. Colombo Branch">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Store Code *</label>
                        <input type="text" name="code" id="store_code" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black uppercase" placeholder="e.g. CMB01">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Phone</label>
                        <input type="text" name="phone" id="store_phone" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="e.g. 011-2345678">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Email</label>
                        <input type="email" name="email" id="store_email" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="e.g. branch@example.com">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Manager</label>
                    <input type="text" name="manager" id="store_manager" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="Store Manager Name">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Address</label>
                    <textarea name="address" id="store_address" rows="2" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="Store Address"></textarea>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Notes</label>
                    <textarea name="notes" id="store_notes" rows="2" class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="Optional notes..."></textarea>
                </div>
                
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="status" id="store_status" value="1" class="w-4 h-4 text-blue-600 rounded border-slate-900 focus:ring-blue-500" checked>
                    <label for="store_status" class="text-xs font-bold text-black cursor-pointer">Active Store</label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4 mt-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Store</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripting -->
    <script>
        function openModal() {
            const modal = document.getElementById('storeModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('storeModal');
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Create Store';
            document.getElementById('storeForm').action = "{{ route('stores.store') }}";
            document.getElementById('formMethod').value = 'POST';
            
            document.getElementById('storeForm').reset();
            document.getElementById('store_status').checked = true;
            document.getElementById('store_status').disabled = false;
            
            openModal();
        }

        function openEditModal(store) {
            document.getElementById('modalTitle').innerText = `Edit Store: ${store.name}`;
            document.getElementById('storeForm').action = `/stores/${store.id}`;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('store_name').value = store.name;
            document.getElementById('store_code').value = store.code;
            document.getElementById('store_phone').value = store.phone || '';
            document.getElementById('store_email').value = store.email || '';
            document.getElementById('store_manager').value = store.manager || '';
            document.getElementById('store_address').value = store.address || '';
            document.getElementById('store_notes').value = store.notes || '';
            document.getElementById('store_status').checked = store.status == 1;
            
            if (store.is_default) {
                document.getElementById('store_status').disabled = true;
                // Add a hidden input to submit the value since disabled checkbox is not submitted
                if(!document.getElementById('hidden_status')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'status';
                    hidden.value = '1';
                    hidden.id = 'hidden_status';
                    document.getElementById('storeForm').appendChild(hidden);
                }
            } else {
                document.getElementById('store_status').disabled = false;
                const hidden = document.getElementById('hidden_status');
                if(hidden) hidden.remove();
            }
            
            openModal();
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Store?',
                text: `Are you sure you want to delete the store "${name}"? This action cannot be undone and will fail if the store has stock.`,
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
