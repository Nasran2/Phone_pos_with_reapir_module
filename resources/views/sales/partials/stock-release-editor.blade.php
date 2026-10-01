<form method="POST" action="{{ route('sales.stock-release.update', $sale) }}" x-data="{ editing: false, released: @js((bool) $sale->stock_deducted_at), original: @js((bool) $sale->stock_deducted_at) }" class="mt-4 rounded-xl border border-amber-200 bg-amber-50/70 p-3 space-y-3">
    @csrf
    @method('PUT')
    <input type="hidden" name="stock_release_enabled" :value="released ? 1 : 0">
    <label class="flex items-start gap-3" :class="!editing ? 'opacity-60' : ''">
        <input type="checkbox" x-model="released" :disabled="!editing" class="mt-1 h-4 w-4 shrink-0 rounded border-amber-400 text-amber-600">
        <span class="text-xs leading-relaxed text-amber-900"><strong class="block text-sm">Release stock with this payment</strong>{{ $sale->stock_deducted_at ? 'Stock has already been released. Edit to return these products to allocated stock.' : 'Products are allocated to this invoice. Edit to release the stock.' }}</span>
    </label>
    <button type="button" x-show="!editing" @click="editing = true" class="text-xs font-bold text-blue-600 underline">Edit stock release</button>
    <div x-show="editing" x-cloak class="flex flex-wrap gap-2">
        <button type="submit" class="rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white">Save Stock Change</button>
        <button type="button" @click="released = original; editing = false" class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-600">Cancel</button>
        <p class="w-full text-xs text-amber-900">Unchecking returns all products to allocated stock. Recorded payments stay unchanged.</p>
    </div>
</form>
