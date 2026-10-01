<x-layouts::app title="Tax Rates Management">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Tax Rates</h1>
            <p class="text-sm text-black">Manage effective-dated tax rates for your business</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Tax Rate
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
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Name / Code</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Type / Class</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Rate</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider">Effective Period</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-center">Status</th>
                        <th class="p-4 text-xs font-bold text-black uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($taxRates as $rate)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <div class="font-medium text-black text-sm">{{ $rate->name }}</div>
                                <div class="text-xs text-black">{{ $rate->code }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-medium text-black">{{ $rate->tax_type }}</div>
                                <div class="text-xs text-black">{{ $rate->tax_classification }}</div>
                            </td>
                            <td class="p-4 text-sm font-bold text-black">
                                {{ floatval($rate->rate) }}%
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-black">{{ $rate->effective_from->format('M d, Y') }}</div>
                                <div class="text-xs text-black">
                                    {{ $rate->effective_to ? 'until ' . $rate->effective_to->format('M d, Y') : 'Ongoing' }}
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                @if($rate->is_active)
                                    <span class="bg-emerald-100 text-emerald-800 py-0.5 px-2 rounded-full text-xs font-medium">Active</span>
                                @else
                                    <span class="bg-rose-100 text-rose-800 py-0.5 px-2 rounded-full text-xs font-medium">Inactive</span>
                                @endif
                                
                                @if($rate->is_default)
                                    <div class="mt-1">
                                        <span class="bg-blue-100 text-blue-800 py-0.5 px-2 rounded-full text-[10px] font-bold uppercase tracking-wide">Default</span>
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-center space-x-2 whitespace-nowrap">
                                <button onclick="openEditModal({{ json_encode($rate) }})" class="text-blue-500 hover:text-blue-700 transition" title="Edit">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <form action="{{ route('accounting.tax-rates.destroy', $rate) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Are you sure you want to delete this tax rate?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 transition" title="Delete">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-black">No tax rates defined. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="tax-rate-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                <h3 id="modal-title" class="font-bold text-black text-lg">Add Tax Rate</h3>
                <button onclick="closeModal()" class="text-black hover:text-black transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto">
                <form id="tax-rate-form" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Tax Name *</label>
                            <input type="text" name="name" id="field-name" required placeholder="e.g. Standard VAT" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Tax Code *</label>
                            <input type="text" name="code" id="field-code" required placeholder="e.g. VAT-15" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm uppercase">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Tax Type *</label>
                            <select name="tax_type" id="field-tax_type" required class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="VAT">VAT</option>
                                <option value="SSCL">SSCL</option>
                                <option value="Customs Duty">Customs Duty</option>
                                <option value="PAL">PAL</option>
                                <option value="CESS">CESS</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Tax Classification *</label>
                            <select name="tax_classification" id="field-tax_classification" required class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="Standard">Standard</option>
                                <option value="Zero Rated">Zero Rated</option>
                                <option value="Exempt">Exempt</option>
                                <option value="Out of Scope">Out of Scope</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Rate (%) *</label>
                            <input type="number" step="0.0001" min="0" name="rate" id="field-rate" required placeholder="e.g. 15.00" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Calculation Method</label>
                            <select name="calculation_method" id="field-calculation_method" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Effective From *</label>
                            <input type="date" name="effective_from" id="field-effective_from" required class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-black mb-1">Effective To (Optional)</label>
                            <input type="date" name="effective_to" id="field-effective_to" class="w-full rounded-lg border-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-6 mt-6 pt-4 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_default" id="field-is_default" value="1" class="rounded border-slate-900 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-black">Set as Default (for this tax type)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="field-is_active" value="1" checked class="rounded border-slate-900 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-black">Active</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-black hover:bg-slate-50 font-medium text-sm transition">Cancel</button>
                <button type="submit" form="tax-rate-form" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm transition shadow-sm">Save Tax Rate</button>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modal-title').innerText = 'Add Tax Rate';
            const form = document.getElementById('tax-rate-form');
            form.reset();
            form.action = "{{ route('accounting.tax-rates.store') }}";
            document.getElementById('form-method').value = 'POST';
            
            document.getElementById('field-tax_type').value = 'VAT';
            document.getElementById('field-tax_classification').value = 'Standard';
            document.getElementById('field-calculation_method').value = 'percentage';
            document.getElementById('field-is_active').checked = true;
            document.getElementById('field-is_default').checked = false;

            document.getElementById('tax-rate-modal').classList.remove('hidden');
        }

        function openEditModal(rate) {
            document.getElementById('modal-title').innerText = 'Edit Tax Rate: ' + rate.name;
            const form = document.getElementById('tax-rate-form');
            form.action = "/accounting/tax-rates/" + rate.id;
            document.getElementById('form-method').value = 'PUT';

            document.getElementById('field-name').value = rate.name;
            document.getElementById('field-code').value = rate.code;
            document.getElementById('field-tax_type').value = rate.tax_type;
            document.getElementById('field-tax_classification').value = rate.tax_classification;
            document.getElementById('field-rate').value = parseFloat(rate.rate);
            document.getElementById('field-calculation_method').value = rate.calculation_method;
            
            // Format dates for input[type=date]
            document.getElementById('field-effective_from').value = rate.effective_from ? rate.effective_from.split('T')[0] : '';
            document.getElementById('field-effective_to').value = rate.effective_to ? rate.effective_to.split('T')[0] : '';
            
            document.getElementById('field-is_default').checked = rate.is_default;
            document.getElementById('field-is_active').checked = rate.is_active;

            document.getElementById('tax-rate-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('tax-rate-modal').classList.add('hidden');
        }
    </script>
</x-layouts::app>
