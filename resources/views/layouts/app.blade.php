<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex-1 flex flex-col h-full min-h-full">
        <div class="flex-1 w-full">
            {{ $slot }}
        </div>
        <div class="mt-auto">
            @include('partials.app-footer')
        </div>
    </flux:main>
</x-layouts::app.sidebar>
