@props(['title', 'summary' => [], 'filters' => true, 'exportPdf' => false, 'exportExcel' => false, 'exportRoute' => null, 'resetRoute' => '#'])

<x-layouts::app :title="$title">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Top Navbar / Header -->
    <div class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800">{{ $title }}</h2>
        </div>
        <div class="flex items-center gap-2">
            @if($exportPdf)
                <a href="{{ $exportRoute ? $exportRoute . '?type=pdf&' . http_build_query(request()->except('type')) : '#' }}" target="_blank" class="flex items-center gap-2 px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold rounded-lg transition shadow-sm border border-rose-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export PDF
                </a>
            @endif
            
            @if($exportExcel)
                <a href="{{ $exportRoute ? $exportRoute . '?type=excel&' . http_build_query(request()->except('type')) : '#' }}" target="_blank" class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 text-xs font-bold rounded-lg transition shadow-sm border border-emerald-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
            @endif
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        
        <!-- Summary Cards Group -->
        @if(count($summary) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ count($summary) > 4 ? 4 : count($summary) }} gap-4">
            @foreach($summary as $label => $value)
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-xs flex flex-col justify-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $label }}</div>
                <div class="text-xl font-extrabold text-slate-850 mt-1">{{ $value }}</div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Filter Bar Card -->
        @if($filters)
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ url()->current() }}" class="flex flex-col gap-4">
                {{ $filterContent ?? '' }}
                
                <div class="flex gap-2 justify-end pt-2 border-t border-slate-100">
                    <a href="{{ $resetRoute }}" class="py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150">Reset Filters</a>
                    <button type="submit" class="py-2 px-6 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs transition duration-150 shadow-sm">Apply Filters</button>
                </div>
            </form>
        </div>
        @endif

        <!-- Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts::app>
