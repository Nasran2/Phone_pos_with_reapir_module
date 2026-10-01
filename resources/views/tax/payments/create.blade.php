<x-layouts::app title="Record Tax Payment">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Record Tax Payment</h1>
            <p class="text-sm text-black">Log a payment made to the tax authorities.</p>
        </div>
        <a href="{{ route('tax.payments.index') }}" class="text-sm font-medium text-black hover:text-black">Cancel & Back</a>
    </div>

    <form action="{{ route('tax.payments.store') }}" method="POST" class="max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        @csrf
        
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="payment_date" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Payment Date *</label>
                    <input type="date" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label for="payment_method" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Online Payment">Online Payment</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label for="tax_period_id" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Allocate to Tax Period *</label>
                <select id="tax_period_id" name="tax_period_id" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">-- Select Period --</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}">
                            {{ $period->period_name }} (Total Payable: Rs. {{ number_format($period->closing_payable_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="total_amount" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Total Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="0.01" id="total_amount" name="total_amount" required placeholder="0.00" class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label for="bank_reference" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Bank Reference / Cheque No.</label>
                    <input type="text" id="bank_reference" name="bank_reference" class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            
            <div>
                <label for="notes" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Internal Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end gap-3">
            <a href="{{ route('tax.payments.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-black text-sm font-medium hover:bg-slate-50 transition">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">Save Draft</button>
        </div>
    </form>
</x-layouts::app>
