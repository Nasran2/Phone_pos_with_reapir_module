<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="app-content flex-1 flex flex-col h-full min-h-full min-w-0">
        <div class="flex-1 w-full min-w-0">
            {{ $slot }}
        </div>
        <div class="mt-auto">
            @include('partials.app-footer')
        </div>
    </flux:main>
</x-layouts::app.sidebar>
