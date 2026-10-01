<x-layouts::app title="Record Tax Adjustment">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black">Record Tax Adjustment</h1>
            <p class="text-sm text-black">Manually adjust a tax period's liability (e.g. for penalties, write-offs, or manual corrections).</p>
        </div>
        <a href="{{ route('tax.adjustments.index') }}" class="text-sm font-medium text-black hover:text-black">Cancel & Back</a>
    </div>

    <form action="{{ route('tax.adjustments.store') }}" method="POST" class="max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        @csrf
        
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="adjustment_date" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Adjustment Date *</label>
                    <input type="date" id="adjustment_date" name="adjustment_date" value="{{ date('Y-m-d') }}" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label for="tax_period_id" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Target Tax Period *</label>
                    <select id="tax_period_id" name="tax_period_id" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Select Period --</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="adjustment_type" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Adjustment Type *</label>
                    <select id="adjustment_type" name="adjustment_type" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="Penalty / Interest">Penalty / Interest</option>
                        <option value="Manual Correction">Manual Correction</option>
                        <option value="Write-off">Write-off</option>
                        <option value="Audit Adjustment">Audit Adjustment</option>
                    </select>
                </div>
                <div>
                    <label for="amount" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" id="amount" name="amount" required placeholder="Use - for decrease, + for increase" class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <p class="text-[10px] text-black mt-1">Positive number increases liability, negative decreases it.</p>
                </div>
            </div>
            
            <div>
                <label for="reason" class="block text-xs font-semibold text-black uppercase tracking-wider mb-1">Detailed Reason *</label>
                <textarea id="reason" name="reason" rows="3" required class="w-full border-slate-900 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end gap-3">
            <a href="{{ route('tax.adjustments.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-black text-sm font-medium hover:bg-slate-50 transition">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">Save Draft</button>
        </div>
    </form>
</x-layouts::app>
