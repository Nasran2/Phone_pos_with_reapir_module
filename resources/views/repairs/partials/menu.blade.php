@if(\App\Support\Features::repairEnabled() && collect(['repair.view','repair.create','repair.report','repair.settings','repair.technicians','repair.assign'])->contains(fn($p)=>auth()->user()->hasPermission($p)))
<div class="py-2">
    <button type="button" onclick="toggleSubmenu(this)" class="w-full text-left rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-[#182647] {{ request()->routeIs('repairs.*') ? 'bg-blue-600 text-white' : '' }}">🔧 Repair Management <span class="chevron-icon float-right">⌄</span></button>
    <div class="pl-5 mt-1 space-y-1 {{ request()->routeIs('repairs.*') ? '' : 'hidden' }}">
        @foreach([['Repair Dashboard','repairs.dashboard','repair.view'],['New Repair Job','repairs.create','repair.create'],['Repair Jobs','repairs.index','repair.view'],['Technicians','repairs.technicians','repair.assign|repair.technicians'],['Repair Reports','repairs.reports','repair.report'],['Repair Settings','repairs.settings','repair.settings']] as [$label,$route,$permissions])
            @if(collect(explode('|',$permissions))->contains(fn($p)=>auth()->user()->hasPermission($p)))<a class="block rounded-lg px-3 py-2 text-xs hover:bg-[#182647] {{ request()->routeIs($route) ? 'text-blue-300' : 'text-slate-400' }}" href="{{ route($route) }}">{{ $label }}</a>@endif
        @endforeach
    </div>
</div>
@endif
