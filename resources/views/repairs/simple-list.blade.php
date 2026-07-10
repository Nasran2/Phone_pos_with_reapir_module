<x-layouts::app :title="$title">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">{{ $title }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @include('repairs.partials.job-table', ['jobs' => $jobs])
                @if($jobs->hasPages())
                    <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">{{ $jobs->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
