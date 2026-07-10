@php($repairErrors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
        {{ session('error') }}
    </div>
@endif

@if ($repairErrors->any())
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-sm">
        <div>Please fix the highlighted repair details.</div>
        <ul class="mt-2 list-disc pl-5 text-xs">
            @foreach ($repairErrors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
