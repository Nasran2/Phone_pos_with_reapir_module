<x-layouts::app :title="__('Stock Adjustment')">
@include('stock.partials.adjustment_styles')
<div class="adj" x-data="bulkAdjustment()">
<header class="adj-head">
<div><a class="adj-back" href="{{ route('stock-adjustments.index') }}">← Adjustment history</a><h1>{{ $adjustment ? 'Edit stock adjustment' : 'New stock adjustment' }}</h1><p class="adj-sub">{{ $adjustment?->adjustment_no ?? 'Update stock quantities for multiple products in one entry.' }}</p></div>
<span class="adj-pill">{{ $storeName }}</span>
</header>
<form method="POST" @submit="saving = true" enctype="multipart/form-data" action="{{ $adjustment ? route('stock-adjustments.update', $adjustment) : route('stock-adjustments.store') }}">
@csrf @if($adjustment) @method('PUT') @endif
<div class="adj-body">
<div class="adj-card"><div class="adj-card-head"><h2>Adjustment details</h2><span class="adj-pill">1 · Details</span></div>
<div class="adj-pad adj-fields">
<label class="adj-field">Adjustment mode<select name="adjustment_type" x-model="mode"><option value="correction">Increase / decrease quantities (+ / −)</option><option value="opening_stock">Set counted / opening stock</option></select></label>
<label class="adj-field">Date<input type="date" name="adjustment_date" required value="{{ old('adjustment_date', $adjustment?->adjustment_date->format('Y-m-d') ?? today()->toDateString()) }}"></label>
<label class="adj-field adj-wide">Reason<input type="text" name="reason" maxlength="255" value="{{ old('reason', $adjustment?->reason) }}" placeholder="e.g. Stock count correction or damaged items"></label>
</div></div>
<div class="adj-layout">
<div class="adj-stack adj-selected-column">
<div class="adj-card" x-ref="selectedProducts">
<div class="adj-card-head"><div><h2>Selected products <span class="adj-pill" x-text="rows.length"></span></h2><p class="adj-sub">Choose the action and quantity for each product.</p></div><span class="adj-pill">2 · Quantities</span></div>
<template x-for="(row, index) in rows" :key="row.product_id">
<section class="adj-line">
<input type="hidden" :name="'items['+index+'][product_id]'" :value="row.product_id">
<input type="hidden" :name="'items['+index+'][quantity]'" :value="mode === 'opening_stock' ? Number(row.amount) : row.sign * Number(row.amount)">
<div class="adj-line-top"><div class="adj-name"><strong x-text="product(row.product_id)?.name"></strong><small x-text="product(row.product_id)?.sku"></small></div><button type="button" class="adj-btn adj-danger" @click="rows.splice(index,1)" :aria-label="'Remove '+product(row.product_id)?.name">Remove</button></div>
<div class="adj-row-fields">
<label class="adj-field" x-show="mode !== 'opening_stock'">Action<select x-model.number="row.sign"><option value="1">+ Increase</option><option value="-1">− Decrease</option></select></label>
<label class="adj-field">Quantity<input type="number" :min="mode === 'opening_stock' ? 0 : 1" step="1" required x-model.number="row.amount"></label>
</div>
<div class="adj-stock"><div><span>Current stock</span><strong x-text="product(row.product_id)?.stock"></strong></div><div><span x-text="mode === 'opening_stock' ? 'Counted' : 'Adjustment'"></span><strong :class="row.sign < 0 && mode !== 'opening_stock' ? 'text-red-600' : 'text-emerald-600'" x-text="(mode === 'opening_stock' ? '' : row.sign > 0 ? '+' : '−') + Number(row.amount || 0)"></strong></div><div><span>After saving</span><strong :class="after(row) < 0 ? 'text-red-600' : 'text-blue-600'" x-text="after(row).toFixed(2)"></strong></div></div>
<p x-show="after(row) < 0" class="adj-sub text-red-600" role="alert">This decrease exceeds the available stock. Reduce the quantity.</p>
</section>
</template>
<div x-show="!rows.length" class="adj-empty"><b>No products selected yet</b><p>Select products in the product picker, then add them here.</p></div>
</div>
</div>
<div class="adj-stack adj-picker-column">
<div class="adj-card adj-picker-card"><div class="adj-card-head"><h2>Find products</h2><span class="adj-pill">Multi-select</span></div><div class="adj-pad">
<label class="adj-field">Search products<input type="search" x-model="search" placeholder="Name, SKU or barcode"></label>
<div class="adj-picker">
<template x-for="product in matches()" :key="product.id"><label class="adj-product"><input type="checkbox" :value="String(product.id)" x-model="selected" :disabled="rows.some(r => r.product_id == product.id)"><span class="adj-name"><strong x-text="product.name"></strong><small x-text="product.sku"></small></span><span class="adj-pill adj-muted" x-text="'Stock: '+product.stock"></span></label></template>
<p x-show="!matches().length" class="adj-empty">No matching products.</p>
</div><button type="button" @click="addSelected()" :disabled="!selected.length" class="adj-btn adj-primary" style="width:100%">Add selected products (<span x-text="selected.length"></span>)</button>
</div></div>
<div class="adj-card adj-notes-card"><div class="adj-card-head"><h2>Additional information</h2><span class="adj-sub">Optional</span></div><div class="adj-pad adj-stack">
<label class="adj-field">Notes<textarea name="notes" placeholder="Add any details about this adjustment">{{ old('notes', $adjustment?->notes) }}</textarea></label>
@unless($adjustment)<label class="adj-field">Supporting document<input type="file" name="document"></label>@endunless
</div></div>
</div>
</div>
<div class="adj-footer"><div><strong><span x-text="rows.length"></span> products selected</strong><p class="adj-sub">Review quantities before saving.</p></div><div class="adj-actions"><a href="{{ route('stock-adjustments.index') }}" class="adj-btn">Cancel</a><button :disabled="saving || !rows.length || rows.some(r => after(r) < 0)" class="adj-btn adj-primary">Save adjustment</button></div></div>
</div></form></div>
<script>
function bulkAdjustment() {
    const products = @json($products->values());
    const previous = @json($initialItems);
    const initial = @json(old('items', $initialItems));
    return {
        products, search: '', selected: [], saving: false, mode: @json(old('adjustment_type', 'correction')),
        rows: initial.map(i => ({product_id: Number(i.product_id), sign: Number(i.quantity) < 0 ? -1 : 1, amount: Math.abs(Number(i.quantity))})),
        product(id) { return this.products.find(p => p.id == id); },
        matches() { const q = this.search.toLowerCase(); return this.products.filter(p => (p.name+' '+p.sku+' '+(p.barcode||'')).toLowerCase().includes(q)); },
        addSelected() { this.selected.forEach(id => { if(!this.rows.some(r => r.product_id == id)) this.rows.push({product_id:Number(id),sign:1,amount:1}); }); this.selected=[]; if (window.innerWidth <= 640) this.$nextTick(() => this.$refs.selectedProducts.scrollIntoView({behavior:'smooth', block:'start'})); },
        after(row) { if (this.mode === 'opening_stock') return Number(row.amount || 0); const old = previous.find(i => i.product_id == row.product_id); return Number(this.product(row.product_id)?.stock || 0) - Number(old?.quantity || 0) + row.sign * Number(row.amount || 0); }
    };
}
</script>
</x-layouts::app>
