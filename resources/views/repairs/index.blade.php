<x-layouts::app title="Repair Jobs"><section class="repair-shell">@include('repairs.partials.header',['heading'=>'Repair jobs','subtitle'=>'Find a device, follow its progress, and settle its balance.'])
<div class="repair-card">@include('repairs.partials.filters') @include('repairs.partials.jobs-table') <div style="margin-top:20px">{{ $jobs->links() }}</div></div>
</section></x-layouts::app>
