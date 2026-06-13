<x-layouts::app :title="__('Suppliers')">
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-850">Suppliers</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $suppliers->total() }} Suppliers</span>
        </div>
        @if(auth()->user()->hasPermission('suppliers.create'))
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-supplier-add'))" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Add Supplier
        </button>
        @endif
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 bg-slate-50" x-data="{ showAdd: false, edit: null }" x-init="window.addEventListener('open-supplier-add', () => showAdd = true)">
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-xs font-semibold">{{ $errors->first() }}</div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200/70 p-4 shadow-xs">
            <form method="GET" action="{{ route('suppliers.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-3 relative">
                    <input name="search" value="{{ request('search') }}" placeholder="Search supplier, company, phone, email..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
                <a href="{{ route('suppliers.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold text-center">Clear</a>
                <button class="px-4 py-2 bg-slate-850 hover:bg-slate-950 text-white rounded-lg text-xs font-bold">Search</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Supplier</th>
                            <th class="px-5 py-4">Contact</th>
                            <th class="px-5 py-4">Tax No</th>
                            <th class="px-5 py-4 text-right">Purchases</th>
                            <th class="px-5 py-4 text-right">Balance</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-850">{{ $supplier->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $supplier->company_name ?: $supplier->address }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div>{{ $supplier->phone ?: '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $supplier->email }}</div>
                            </td>
                            <td class="px-5 py-4">{{ $supplier->tax_number ?: '-' }}</td>
                            <td class="px-5 py-4 text-right font-semibold">{{ $supplier->purchases_count }}</td>
                            <td class="px-5 py-4 text-right font-black {{ (float) $supplier->balance > 0 ? 'text-red-600' : 'text-emerald-700' }}">Rs. {{ number_format((float) $supplier->balance, 2) }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                    @if(auth()->user()->hasPermission('suppliers.edit'))
                                    @php
                                        $supplierEditPayload = [
                                            'action' => route('suppliers.update', $supplier),
                                            'name' => $supplier->name,
                                            'company_name' => $supplier->company_name,
                                            'phone' => $supplier->phone,
                                            'email' => $supplier->email,
                                            'tax_number' => $supplier->tax_number,
                                            'balance' => (float) $supplier->balance,
                                            'address' => $supplier->address,
                                        ];
                                    @endphp
                                    <button type="button" @click="edit = {{ Illuminate\Support\Js::from($supplierEditPayload) }}" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4v16h16v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
                                    @endif
                                    @if(auth()->user()->hasPermission('suppliers.delete'))
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete supplier {{ $supplier->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1.5 rounded-lg text-red-600 hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400 font-semibold">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $suppliers->links() }}</div>
        </div>

        @if(auth()->user()->hasPermission('suppliers.create'))
        <div x-show="showAdd" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4">
            <div @click.away="showAdd = false" class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-black text-slate-850">Add Supplier</h3>
                    <button type="button" @click="showAdd = false" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form method="POST" action="{{ route('suppliers.store') }}" class="p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <input name="name" required placeholder="Supplier name" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="company_name" placeholder="Company name" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="phone" placeholder="Phone" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="email" type="email" placeholder="Email" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="tax_number" placeholder="Tax no" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="balance" type="number" step="0.01" value="0" placeholder="Opening balance" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <textarea name="address" rows="2" placeholder="Address" class="md:col-span-2 px-3 py-2 border border-slate-200 rounded-lg text-xs"></textarea>
                    <div class="md:col-span-4 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showAdd = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold">Cancel</button>
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasPermission('suppliers.edit'))
        <div x-show="edit" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4">
            <div @click.away="edit = null" class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-black text-slate-850">Edit Supplier</h3>
                    <button type="button" @click="edit = null" class="text-slate-400 hover:text-slate-700">&times;</button>
                </div>
                <form method="POST" :action="edit ? edit.action : '#'" class="p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf @method('PUT')
                    <input name="name" required :value="edit?.name" placeholder="Supplier name" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="company_name" :value="edit?.company_name" placeholder="Company name" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="phone" :value="edit?.phone" placeholder="Phone" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="email" type="email" :value="edit?.email" placeholder="Email" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="tax_number" :value="edit?.tax_number" placeholder="Tax no" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <input name="balance" type="number" step="0.01" :value="edit?.balance" placeholder="Balance" class="px-3 py-2 border border-slate-200 rounded-lg text-xs">
                    <textarea name="address" rows="2" x-text="edit?.address" placeholder="Address" class="md:col-span-2 px-3 py-2 border border-slate-200 rounded-lg text-xs"></textarea>
                    <div class="md:col-span-4 flex justify-end gap-2 pt-2">
                        <button type="button" @click="edit = null" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold">Cancel</button>
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold">Update Supplier</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</x-layouts::app>
