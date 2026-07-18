<x-layouts::app title="New Tax Period">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">New Tax Period</h1>
            <p class="text-sm text-slate-500">Open a new reporting period for tax calculation.</p>
        </div>
        <a href="{{ route('tax.periods.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel & Back</a>
    </div>

    <form action="{{ route('tax.periods.store') }}" method="POST" class="max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        @csrf
        
        <div class="space-y-6">
            <div>
                <label for="period_name" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Period Name *</label>
                <input type="text" id="period_name" name="period_name" placeholder="e.g. July 2026" required class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" required class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">End Date *</label>
                    <input type="date" id="end_date" name="end_date" required class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            
            <div>
                <label for="due_date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Payment Due Date (Optional)</label>
                <input type="date" id="due_date" name="due_date" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                <p class="text-xs text-slate-400 mt-1">Leave blank if there is no strict deadline for this tax period.</p>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end gap-3">
            <a href="{{ route('tax.periods.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">Create Period</button>
        </div>
    </form>
</x-layouts::app>
