@include('repairs.partials.styles')
<header class="repair-heading"><div><p class="repair-eyebrow">DEVICE SERVICE WORKSPACE</p><h1>{{ $heading }}</h1><p class="repair-muted">{{ $subtitle ?? 'Every device, part and payment in one place.' }}</p></div>
@if(auth()->user()->hasPermission('repair.create'))<a class="repair-button primary" href="{{ route('repairs.create') }}">＋ New repair job</a>@endif</header>
<nav class="repair-nav" aria-label="Repair workspace">
@foreach([['Overview','repairs.dashboard','repair.view'],['Jobs','repairs.index','repair.view'],['Technicians','repairs.technicians','repair.assign|repair.technicians'],['Reports','repairs.reports','repair.report'],['Settings','repairs.settings','repair.settings']] as [$label,$route,$permissions])
    @if(collect(explode('|',$permissions))->contains(fn($p)=>auth()->user()->hasPermission($p)))<a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}">{{ $label }}</a>@endif
@endforeach</nav>
@if(session('success'))<div class="repair-notice success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="repair-notice error" role="alert"><strong>Check these details</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
