<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
        @include('partials.app-footer')
    </flux:main>
</x-layouts::app.sidebar>
