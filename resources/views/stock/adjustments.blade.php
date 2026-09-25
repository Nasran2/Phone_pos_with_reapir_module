<x-layouts::app :title="__('Stock Adjustments')">
@include('stock.partials.adjustment_styles')
<div class="adj">
<header class="adj-head"><div><h1>Stock Adjustments <span class="adj-pill">{{ $adjustments->total() }} Records</span></h1><p class="adj-sub">Manage stock corrections and review every movement.</p></div><div class="adj-actions">
<a href="{{ route('stock-adjustments.index', request()->except('page') + ['type' => 'pdf']) }}" class="adj-btn">↓ Download PDF</a>
@if(auth()->user()->hasPermission('stock.adjustment.create'))<a href="{{ route('stock-adjustments.create') }}" class="adj-btn adj-primary">+ New adjustment</a>@endif
</div></header>
<div class="adj-body">
<form method="GET" class="adj-card adj-pad adj-filter">
<label class="adj-field">Search history<input type="search" name="search" value="{{ request('search') }}" placeholder="Reference or reason"></label>
<label class="adj-field">From date<input type="date" name="start_date" value="{{ request('start_date') }}"></label>
<label class="adj-field">To date<input type="date" name="end_date" value="{{ request('end_date') }}"></label>
<div class="adj-actions"><button class="adj-btn adj-primary">Filter</button><a href="{{ route('stock-adjustments.index') }}" class="adj-btn">Reset</a></div>
</form>
<div class="adj-card"><div class="adj-card-head"><h2>Adjustment history</h2><span class="adj-sub">{{ $adjustments->total() }} results</span></div>
<table class="adj-table"><thead><tr><th>Reference / Reason</th><th>Date</th><th>Products</th><th>Created by</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($adjustments as $adjustment)
<tr><td data-label="Reference"><a href="{{ route('stock-adjustments.show', $adjustment) }}">{{ $adjustment->adjustment_no }}</a><p class="adj-sub">{{ $adjustment->reason ?: 'No reason provided' }}</p></td>
<td data-label="Date">{{ $adjustment->adjustment_date->format('d M Y') }}</td>
<td data-label="Products"><span class="adj-pill">{{ $adjustment->items_count }} products</span></td>
<td data-label="Created by">{{ $adjustment->creator?->name ?? '—' }}</td>
<td data-label="Status"><span class="adj-pill {{ $adjustment->status === 'cancelled' ? 'adj-negative' : 'adj-positive' }}">{{ $adjustment->status === 'cancelled' ? 'Deleted / reversed' : 'Approved' }}</span></td>
<td data-label="Action"><a class="adj-btn" href="{{ route('stock-adjustments.show', $adjustment) }}" aria-label="View {{ $adjustment->adjustment_no }}">View details →</a></td></tr>
@empty<tr><td colspan="6"><div class="adj-empty"><b>No adjustments found</b><p>Try another search or create a new stock adjustment.</p></div></td></tr>@endforelse
</tbody></table></div>
<div>{{ $adjustments->links() }}</div>
</div></div>
</x-layouts::app>
