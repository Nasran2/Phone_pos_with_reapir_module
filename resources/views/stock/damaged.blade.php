<x-layouts::app :title="__('Damaged Stock Management')">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-black">Damaged Stock</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">
                {{ $damagedStocks->total() }} Logged Records
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('damaged_stock.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Damaged Item</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        
        <!-- Alerts block -->
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

        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('damaged-stocks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search Damage No, reason, product..." 
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-black transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-black">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <select 
                        name="status" 
                        class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-black transition bg-white"
                    >
                        <option value="">All Statuses</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Repaired" {{ request('status') === 'Repaired' ? 'selected' : '' }}>Repaired</option>
                        <option value="Disposed" {{ request('status') === 'Disposed' ? 'selected' : '' }}>Disposed</option>
                        <option value="Returned to Stock" {{ request('status') === 'Returned to Stock' ? 'selected' : '' }}>Returned to Stock</option>
                    </select>
                </div>

                <div></div>

                <!-- Submit & Clear Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('damaged-stocks.index') }}" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-black font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Damaged Stocks Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-black text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">Damage No</th>
                            <th class="px-6 py-4">Product Name</th>
                            <th class="px-6 py-4 text-center">Quantity</th>
                            <th class="px-6 py-4">Damage Type</th>
                            <th class="px-6 py-4 text-right">Estimated Loss</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4">Responsible</th>
                            <th class="px-6 py-4">Created Date</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-black text-xs">
                        @forelse($damagedStocks as $dmg)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-black">
                                {{ $dmg->damage_no }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-black">{{ $dmg->product->name ?? 'Deleted Product' }}</div>
                                <div class="text-[10px] text-black font-mono">{{ $dmg->product->sku ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center font-bold">
                                {{ number_format($dmg->quantity, 0) }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-black">
                                {{ $dmg->damage_type }}
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold text-rose-600">
                                Rs. {{ number_format($dmg->estimated_loss, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase
                                    @if($dmg->status === 'Returned to Stock') bg-emerald-100 text-emerald-800
                                    @elseif($dmg->status === 'Pending') bg-amber-100 text-amber-800
                                    @elseif($dmg->status === 'Repaired') bg-blue-100 text-blue-800
                                    @else bg-slate-100 text-black @endif
                                ">
                                    {{ $dmg->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-black">
                                {{ $dmg->responsible_person ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-black">
                                {{ $dmg->created_at ? $dmg->created_at->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Return to Stock Quick Button -->
                                    @if(auth()->user()->hasPermission('damaged_stock.return') && $dmg->status !== 'Returned to Stock')
                                    <form method="POST" action="{{ route('damaged-stocks.status', $dmg->id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="Returned to Stock">
                                        <button 
                                            type="submit" 
                                            class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-600 hover:bg-blue-700 text-white rounded transition shadow-xs cursor-pointer flex items-center gap-1"
                                            title="Return items back to inventory stock"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3"/>
                                            </svg>
                                            <span>Return Stock</span>
                                        </button>
                                    </form>
                                    @endif

                                    <!-- Status Update Selection Dropdown -->
                                    @if(auth()->user()->hasPermission('damaged_stock.edit') && $dmg->status !== 'Returned to Stock')
                                    <div class="relative inline-block text-left">
                                        <select 
                                            onchange="updateDmgStatus({{ $dmg->id }}, this.value)"
                                            class="px-2 py-1 border border-slate-200 rounded text-[10px] bg-slate-50 font-bold focus:outline-none cursor-pointer"
                                        >
                                            <option value="">Status</option>
                                            <option value="Pending" {{ $dmg->status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="Repaired" {{ $dmg->status === 'Repaired' ? 'selected' : '' }}>Repaired</option>
                                            <option value="Disposed" {{ $dmg->status === 'Disposed' ? 'selected' : '' }}>Disposed</option>
                                        </select>
                                    </div>
                                    @endif

                                    @if(auth()->user()->hasPermission('damaged_stock.edit'))
                                    <!-- Edit Details -->
                                    <button 
                                        onclick="openEditModal({{ json_encode($dmg) }})"
                                        class="p-1.5 text-black hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                        title="Edit Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('damaged_stock.delete'))
                                    <!-- Delete Record -->
                                    <button 
                                        onclick="confirmDelete({{ $dmg->id }}, '{{ $dmg->damage_no }}')"
                                        class="p-1.5 text-black hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                        title="Delete Damage Record"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <form id="delete-form-{{ $dmg->id }}" method="POST" action="{{ route('damaged-stocks.destroy', $dmg->id) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-black">
                                <svg class="w-10 h-10 mx-auto text-black mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No damaged stock logs recorded.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            @if($damagedStocks->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $damagedStocks->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- LOG NEW DAMAGE RECORD MODAL -->
    <div id="damageModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container text-black">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-855" id="modalTitle">Log Damaged Item</h3>
                <button onclick="closeModal('damageModal')" class="text-black hover:text-black text-lg cursor-pointer">✕</button>
            </div>
            <form id="damageForm" method="POST" action="" class="p-6 space-y-4" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div id="productSelectField">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Select Product *</label>
                    <select 
                        name="product_id" 
                        id="dmg_product_id" 
                        onchange="calculateEstimatedLoss()"
                        class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white"
                    >
                        <option value="">-- Choose Product --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-cost="{{ (float) $prod->cost_price }}">
                                {{ $prod->sku }} - {{ $prod->name }} (Rs. {{ number_format($prod->cost_price, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Quantity *</label>
                        <input 
                            type="number" 
                            name="quantity" 
                            id="dmg_quantity" 
                            required 
                            min="1" 
                            oninput="calculateEstimatedLoss()" 
                            class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" 
                            placeholder="1"
                        >
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Damage Type *</label>
                        <select 
                            name="damage_type" 
                            id="dmg_type" 
                            required 
                            class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white"
                        >
                            <option value="Screen Crack">Screen Crack</option>
                            <option value="Water Damage">Water Damage</option>
                            <option value="Battery Swell">Battery Swell</option>
                            <option value="Impact Fracture">Impact Fracture</option>
                            <option value="Factory Defect">Factory Defect</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Estimated Loss (Rs.)</label>
                        <input 
                            type="text" 
                            id="dmg_estimated_loss" 
                            readonly 
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-900 rounded-lg text-sm font-bold text-rose-600 focus:outline-none" 
                            placeholder="Rs. 0.00"
                        >
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Responsible Person</label>
                        <input 
                            type="text" 
                            name="responsible_person" 
                            id="dmg_responsible" 
                            class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" 
                            placeholder="e.g. Sales Executive"
                        >
                    </div>
                </div>

                <div id="documentUploadField">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Attach Proof Photo / Document</label>
                    <input 
                        type="file" 
                        name="document" 
                        class="w-full text-sm text-black file:mr-4 file:py-2.5.5 file:px-3.5 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                    >
                </div>

                <div id="statusSelectionField">
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Initial Status *</label>
                    <select 
                        name="status" 
                        id="dmg_status" 
                        class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black bg-white"
                    >
                        <option value="Pending">Pending Audit</option>
                        <option value="Repaired">Repaired (Stays out of stock)</option>
                        <option value="Disposed">Disposed / Scrapped</option>
                        <option value="Returned to Stock">Returned to Stock</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Reason *</label>
                    <textarea 
                        name="reason" 
                        id="dmg_reason" 
                        required 
                        rows="2" 
                        class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" 
                        placeholder="Explain exactly how the damage occurred..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-black uppercase tracking-wide mb-1.5">Notes</label>
                    <textarea 
                        name="notes" 
                        id="dmg_notes" 
                        rows="1" 
                        class="w-full px-3 py-2.5 border border-slate-900 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-black" 
                        placeholder="Additional remarks..."
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 -mx-6 -mb-6 p-4">
                    <button type="button" onclick="closeModal('damageModal')" class="px-4 py-2 border border-slate-200 text-black hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Log</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden form for dynamic status updates -->
    <form id="statusUpdateForm" method="POST" action="" class="hidden">
        @csrf
        <input type="hidden" name="status" id="statusUpdateInput">
    </form>

    <!-- UI Scripts -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        @if(auth()->user()->hasPermission('damaged_stock.return'))
        function updateDmgStatus(id, newStatus) {
            if (!newStatus) return;
            Swal.fire({
                title: 'Update Status?',
                text: `Are you sure you want to change the status of this record to "${newStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Update',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('statusUpdateForm');
                    form.action = `/damaged-stocks/${id}/status`;
                    document.getElementById('statusUpdateInput').value = newStatus;
                    form.submit();
                }
            });
        }
        @endif

        function closeModal(id) {
            const modal = document.getElementById(id);
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function calculateEstimatedLoss() {
            const select = document.getElementById('dmg_product_id');
            const qtyInput = document.getElementById('dmg_quantity');
            const lossInput = document.getElementById('dmg_estimated_loss');

            const qty = parseFloat(qtyInput.value) || 0;
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption || !selectedOption.value) {
                lossInput.value = 'Rs. 0.00';
                return;
            }

            const costPrice = parseFloat(selectedOption.getAttribute('data-cost')) || 0;
            const estimatedLoss = qty * costPrice;
            lossInput.value = 'Rs. ' + estimatedLoss.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Log Damaged Item';
            document.getElementById('damageForm').action = "{{ route('damaged-stocks.store') }}";
            document.getElementById('formMethod').value = 'POST';
            
            // Show fields needed for create
            document.getElementById('productSelectField').style.display = 'block';
            document.getElementById('statusSelectionField').style.display = 'block';
            document.getElementById('documentUploadField').style.display = 'block';

            // Clear values
            document.getElementById('dmg_product_id').value = '';
            document.getElementById('dmg_quantity').value = '';
            document.getElementById('dmg_type').value = 'Screen Crack';
            document.getElementById('dmg_responsible').value = '';
            document.getElementById('dmg_status').value = 'Pending';
            document.getElementById('dmg_reason').value = '';
            document.getElementById('dmg_notes').value = '';
            document.getElementById('dmg_estimated_loss').value = 'Rs. 0.00';

            openModal('damageModal');
        }

        function openEditModal(dmg) {
            document.getElementById('modalTitle').innerText = `Edit Damage Log: ${dmg.damage_no}`;
            document.getElementById('damageForm').action = `/damaged-stocks/${dmg.id}`;
            document.getElementById('formMethod').value = 'PUT';

            // Hide fields not editable
            document.getElementById('productSelectField').style.display = 'none';
            document.getElementById('statusSelectionField').style.display = 'none';
            document.getElementById('documentUploadField').style.display = 'none';

            // Set values
            document.getElementById('dmg_type').value = dmg.damage_type;
            document.getElementById('dmg_responsible').value = dmg.responsible_person || '';
            document.getElementById('dmg_reason').value = dmg.reason;
            document.getElementById('dmg_notes').value = dmg.notes || '';
            document.getElementById('dmg_estimated_loss').value = 'Rs. ' + parseFloat(dmg.estimated_loss).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

            openModal('damageModal');
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Damage Record?',
                text: `Are you sure you want to delete damaged stock log "${name}"? If active, inventory levels will be reverted back.`,
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
