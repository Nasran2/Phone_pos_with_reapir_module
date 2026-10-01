<x-layouts::app title="HS Codes Management">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">HS Codes</h1>
            <p class="text-sm text-black">Manage Harmonized System codes and their import levies</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add HS Code
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-700 p-4 rounded-lg border border-emerald-100 font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-rose-50 text-rose-700 p-4 rounded-lg border border-rose-100 font-medium">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">HS Code / Desc</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">Customs Duty</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">PAL</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">CESS</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-right">SSCL/SRID</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-center">Status</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($hsCodes as $code)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-black text-sm">{{ $code->code }}</div>
                                <div class="text-xs text-black">{{ $code->description }}</div>
                            </td>
                            <td class="p-4 text-sm font-medium text-black text-right">{{ floatval($code->customs_duty_rate) }}%</td>
                            <td class="p-4 text-sm font-medium text-black text-right">{{ floatval($code->pal_rate) }}%</td>
                            <td class="p-4 text-sm font-medium text-black text-right">{{ floatval($code->cess_rate) }}%</td>
                            <td class="p-4 text-sm font-medium text-black text-right">{{ floatval($code->sscl_rate + $code->srid_rate) }}%</td>
                            <td class="p-4 text-center">
                                @if($code->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-black">Inactive</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <button type="button" onclick="openEditModal({{ $code->toJson() }})" class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form action="{{ route('hs-codes.destroy', $code->id) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Delete this HS Code?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-black">
                                No HS Codes found. <button type="button" onclick="openCreateModal()" class="text-blue-600 hover:underline">Add one now</button>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="hsCodeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden" @click.stop>
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 id="modalTitle" class="text-lg font-bold text-black">Add HS Code</h3>
                <button type="button" onclick="closeModal()" class="text-black hover:text-black transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="hsCodeForm" method="POST" action="{{ route('hs-codes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">HS Code *</label>
                            <input type="text" name="code" id="code" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-black mb-1">Description *</label>
                            <input type="text" name="description" id="description" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">Customs Duty (%) *</label>
                            <input type="number" step="0.01" min="0" name="customs_duty_rate" id="customs_duty_rate" value="0" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">PAL (%) *</label>
                            <input type="number" step="0.01" min="0" name="pal_rate" id="pal_rate" value="0" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">CESS (%) *</label>
                            <input type="number" step="0.01" min="0" name="cess_rate" id="cess_rate" value="0" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black mb-1">SSCL (%) *</label>
                            <input type="number" step="0.01" min="0" name="sscl_rate" id="sscl_rate" value="0" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-black mb-1">SRID / Other (%)</label>
                            <input type="number" step="0.01" min="0" name="srid_rate" id="srid_rate" value="0" class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-black mb-1">Effective From *</label>
                            <input type="date" name="effective_from" id="effective_from" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-900 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        
                        <div class="col-span-2 flex items-center gap-2 mt-2">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded border-slate-900 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-black">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-black hover:text-black transition-colors">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">Save HS Code</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('hsCodeModal');
        const form = document.getElementById('hsCodeForm');
        
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add HS Code';
            document.getElementById('formMethod').value = 'POST';
            form.action = "{{ route('hs-codes.store') }}";
            
            form.reset();
            document.getElementById('effective_from').value = "{{ date('Y-m-d') }}";
            
            modal.style.display = 'flex';
        }

        function openEditModal(code) {
            document.getElementById('modalTitle').textContent = 'Edit HS Code';
            document.getElementById('formMethod').value = 'PUT';
            form.action = `/hs-codes/${code.id}`;
            
            document.getElementById('code').value = code.code;
            document.getElementById('description').value = code.description;
            document.getElementById('customs_duty_rate').value = code.customs_duty_rate;
            document.getElementById('pal_rate').value = code.pal_rate;
            document.getElementById('cess_rate').value = code.cess_rate;
            document.getElementById('srid_rate').value = code.srid_rate;
            document.getElementById('sscl_rate').value = code.sscl_rate;
            document.getElementById('effective_from').value = code.effective_from;
            document.getElementById('is_active').checked = code.is_active;
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }
        
        // Close modal on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</x-layouts::app>
