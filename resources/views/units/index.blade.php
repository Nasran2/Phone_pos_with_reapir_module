<x-layouts::app :title="__('Unit Management')">
    <!-- SweetAlert2 CDN for modern popup alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black dark:text-white">Product Measurement Units</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $units->count() }} Units
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('units.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Unit</span>
            </button>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <!-- Success & Error Alert Messages -->
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium">
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
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium">
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
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Units Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden max-w-4xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Unit Name</th>
                            <th class="px-6 py-4">Short Name</th>
                            <th class="px-6 py-4">Associated Products</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-black text-xs">
                        @forelse($units as $unit)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-bold text-black">
                                {{ $unit->name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-[11px] text-black">
                                {{ $unit->short_name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-amber-50 text-amber-700 font-bold rounded-md text-[10px]">
                                    {{ $unit->products_count }} Products
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->hasPermission('units.edit'))
                                    <button 
                                        onclick="openEditModal({{ json_encode($unit) }})"
                                        class="p-1.5 text-black hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                        title="Edit Unit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('units.delete'))
                                    <button 
                                        onclick="confirmDelete({{ $unit->id }}, '{{ $unit->name }}')"
                                        class="p-1.5 text-black hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                        title="Delete Unit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <form id="delete-form-{{ $unit->id }}" method="POST" action="{{ route('units.destroy', $unit->id) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-black">
                                <svg class="w-10 h-10 mx-auto text-black mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No units registered yet.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- UNIT CREATE / EDIT MODAL -->
    <div id="unitModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-black" id="modalTitle">Create Unit</h3>
                <button onclick="closeModal()" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <form id="unitForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Unit Full Name *</label>
                    <input type="text" name="name" id="unit_name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="e.g. Piece">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Short Name *</label>
                    <input type="text" name="short_name" id="unit_short_name" required class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black" placeholder="e.g. pcs">
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Unit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripting -->
    <script>
        function openModal() {
            const modal = document.getElementById('unitModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('unitModal');
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Create Unit';
            document.getElementById('unitForm').action = "{{ route('units.store') }}";
            document.getElementById('formMethod').value = 'POST';
            
            document.getElementById('unit_name').value = '';
            document.getElementById('unit_short_name').value = '';
            
            openModal();
        }

        function openEditModal(unit) {
            document.getElementById('modalTitle').innerText = `Edit Unit: ${unit.name}`;
            document.getElementById('unitForm').action = `/units/${unit.id}`;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('unit_name').value = unit.name;
            document.getElementById('unit_short_name').value = unit.short_name;
            
            openModal();
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Unit?',
                text: `Are you sure you want to delete the unit "${name}"?`,
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
