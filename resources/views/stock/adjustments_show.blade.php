<x-layouts::app :title="__('Stock Adjustment Details')">
@include('stock.partials.adjustment_styles')
<div class="adj">
<header class="adj-head"><div><a class="adj-back" href="{{ route('stock-adjustments.index') }}">← Adjustment history</a><h1>{{ $stockAdjustment->adjustment_no }}</h1><p class="adj-sub">Stock adjustment details and movement history</p></div>
<div class="adj-actions">
<a class="adj-btn" href="{{ route('stock-adjustments.pdf', $stockAdjustment) }}">Download PDF</a>
@if($stockAdjustment->status !== 'cancelled')
@if(auth()->user()->hasPermission('stock.adjustment.edit'))<a class="adj-btn adj-primary" href="{{ route('stock-adjustments.edit', $stockAdjustment) }}">Edit adjustment</a>@endif
@if(auth()->user()->hasPermission('stock.adjustment.delete'))<form method="POST" action="{{ route('stock-adjustments.destroy', $stockAdjustment) }}" onsubmit="return confirmAction(event, 'Delete this adjustment and reverse its stock changes? History will be retained.')">@csrf @method('DELETE')<button class="adj-btn adj-danger" style="width:100%">Delete adjustment</button></form>@endif
@endif
</div></header>
<div class="adj-body">
<div class="adj-card"><div class="adj-card-head"><h2>Adjustment summary</h2><span class="adj-pill {{ $stockAdjustment->status === 'cancelled' ? 'adj-negative' : 'adj-positive' }}">{{ $stockAdjustment->status === 'cancelled' ? 'Deleted / reversed' : 'Approved' }}</span></div>
<div class="adj-pad"><dl class="adj-meta"><div><dt>Adjustment date</dt><dd>{{ $stockAdjustment->adjustment_date->format('d M Y') }}</dd></div><div><dt>Store</dt><dd>{{ $storeName }}</dd></div><div><dt>Created by</dt><dd>{{ $stockAdjustment->creator?->name ?? '—' }}</dd></div><div><dt>Products</dt><dd>{{ $stockAdjustment->items->count() }}</dd></div></dl>
<div class="adj-note"><span class="adj-sub">Reason</span><p>{{ $stockAdjustment->reason ?: 'No reason provided' }}</p>
@if($stockAdjustment->notes)<p class="adj-sub whitespace-pre-wrap">{{ $stockAdjustment->notes }}</p>@endif
@if($stockAdjustment->document_path)<a class="adj-back" href="{{ asset('storage/'.$stockAdjustment->document_path) }}" target="_blank" rel="noopener">View supporting document ↗</a>@endif
@if($stockAdjustment->status === 'cancelled')<p class="adj-sub">Deleted — stock reversed; history retained</p>@endif
</div></div></div>
<div class="adj-card"><div class="adj-card-head"><div><h2>Products</h2><p class="adj-sub">{{ $stockAdjustment->status === 'cancelled' ? 'Original quantities before this adjustment was reversed.' : 'Stock quantities recorded for this adjustment.' }}</p></div><span class="adj-pill">{{ $stockAdjustment->items->count() }} products</span></div>
<table class="adj-table"><thead><tr><th>Product / SKU</th><th>Before</th><th>Adjustment</th><th>After</th></tr></thead><tbody>
@foreach($stockAdjustment->items as $item)
<tr><td data-label="Product"><strong>{{ $item->product?->name ?? 'Archived product' }}</strong><p class="adj-sub">{{ $item->product?->sku }}</p></td><td data-label="Before">{{ $item->current_stock }}</td><td data-label="Adjustment"><span class="adj-pill {{ $item->quantity < 0 ? 'adj-negative' : 'adj-positive' }}">{{ $item->quantity > 0 ? '+' : '' }}{{ $item->quantity }}</span></td><td data-label="After"><strong>{{ $item->new_stock }}</strong></td></tr>
@endforeach
</tbody></table></div>
<div class="adj-card"><div class="adj-card-head"><div><h2>Movement history</h2><p class="adj-sub">Every save and reversal is retained here, including earlier edits.</p></div><span class="adj-pill">{{ $history->count() }} entries</span></div>
@forelse($history as $movement)
<div class="adj-movement">
<span aria-hidden="true" class="adj-dot {{ $movement->quantity_change < 0 ? 'adj-negative' : 'adj-positive' }}">{{ $movement->quantity_change < 0 ? '−' : '+' }}</span>
<div class="adj-name"><strong>{{ $movement->product?->name ?? 'Archived product' }}</strong><p class="adj-sub">{{ $movement->created_at->format('d M Y · H:i') }} · {{ $movement->creator?->name ?? '—' }}</p><p class="adj-sub">{{ $movement->description }}</p></div>
<div class="adj-stock"><div><span>Before</span><strong>{{ $movement->quantity_before }}</strong></div><div><span>Change</span><strong class="{{ $movement->quantity_change < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</strong></div><div><span>After</span><strong>{{ $movement->new_quantity }}</strong></div></div>
</div>
@empty<div class="adj-empty">No movements recorded.</div>@endforelse
</div></div></div>
</x-layouts::app>
