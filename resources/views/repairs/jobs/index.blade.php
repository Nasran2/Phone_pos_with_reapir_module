<x-layouts::app :title="__('Repair Job Cards')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Dashboard / Repair Management / Job Cards</nav>
                <h1 class="mt-2 text-2xl font-black text-slate-900">Repair Job Cards</h1>
                <p class="mt-1 text-sm text-slate-500">Search every service job by customer, phone, job number, IMEI, or serial.</p>
            </div>
            @if(auth()->user()->hasPermission('repair.create'))
                <a href="{{ route('repairs.jobs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Device Check-In</a>
            @endif
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('repairs.jobs.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_260px_auto]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search job, customer, phone, IMEI, serial..." class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <select name="status" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <button class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-extrabold text-white hover:bg-slate-800">Filter</button>
                        <a href="{{ route('repairs.jobs.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-extrabold text-slate-600 hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @include('repairs.partials.job-table', ['jobs' => $jobs])
                @if($jobs->hasPages())
                    <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">{{ $jobs->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
