<nav class="mobile-bottom-nav lg:hidden" aria-label="Quick navigation">
    @php
        $quickLinks = [
            ['dashboard', 'dashboard.view', 'Home', 'M3 10l9-7 9 7v11h-6v-7H9v7H3z'],
            ['pos.retail', 'retail_pos.view', 'Retail', 'M3 3h2l3 12h11l3-9H6M9 21h.01M19 21h.01'],
            ['pos.wholesale', 'wholesale_pos.view', 'Wholesale', 'M3 7l9-4 9 4-9 4zM3 7v10l9 4 9-4V7M12 11v10'],
            ['accounting.expenses.index', 'expenses.view', 'Expense', 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6'],
        ];
    @endphp
    @foreach($quickLinks as [$quickRoute, $quickPermission, $quickLabel, $quickIcon])
        @if(auth()->user()->hasPermission($quickPermission) && ($quickRoute !== 'pos.retail' || $moduleSettings['retail_pos']) && ($quickRoute !== 'pos.wholesale' || $moduleSettings['wholesale_pos']) && ($quickRoute !== 'accounting.expenses.index' || $moduleSettings['expense']))
            <a href="{{ route($quickRoute) }}" @if(request()->routeIs($quickRoute)) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="{{ $quickIcon }}"/></svg>
                <span>{{ $quickLabel }}</span>
            </a>
        @endif
    @endforeach
    <button type="button" onclick="toggleMobileSidebar(true)" aria-controls="mobile-sidebar" aria-label="More navigation">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span>More</span>
    </button>
</nav>
