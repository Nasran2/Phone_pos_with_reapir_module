<div class="global-search relative min-w-0 flex-1" data-search-url="{{ route('global-search') }}">
    <label for="global-search-input" class="sr-only">Search products, IMEI, invoices and contacts</label>
    <input id="global-search-input" type="search" autocomplete="off" placeholder="Search products, invoices, contacts…" class="w-full rounded-lg border bg-slate-50 text-sm" aria-controls="global-search-results" aria-expanded="false">
    <div id="global-search-results" class="search-results hidden absolute left-0 right-0 z-40 rounded-xl border bg-white p-2 shadow-lg" aria-live="polite"></div>
</div>
