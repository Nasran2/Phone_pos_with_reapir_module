@php
    $canUpdateSettings = auth()->user()->hasPermission('settings.' . $section['route'] . '.update');
    $settingsErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $fieldHelp = function (array $field): string {
        if (!empty($field['hint'])) {
            return $field['hint'];
        }

        $label = $field['label'] ?? 'setting';
        $type = $field['type'] ?? 'text';

        $help = match ($type) {
            'toggle' => "Turn {$label} on or off. This controls whether this feature is available in the system.",
            'select' => "Choose the default {$label} option used by the related module.",
            'file' => "Upload or replace the {$label} used by invoices, reports, and screens where applicable.",
            'textarea' => "Enter the {$label} text used by the related module.",
            'number' => "Set the numeric value for {$label}.",
            'email' => "Enter the email address used for {$label}.",
            'url' => "Enter the website or API URL used for {$label}.",
            'password' => "Enter or update the secure key or secret for {$label}.",
            default => "Set the {$label} used by this settings page.",
        };

        if (array_key_exists('default', $field) && $field['default'] !== null && $field['default'] !== '') {
            $default = is_bool($field['default']) ? ($field['default'] ? 'Enabled' : 'Disabled') : (string) $field['default'];
            $help .= " Default: {$default}.";
        }

        return $help;
    };
@endphp

<x-layouts::app :title="$section['label']">
    <div class="bg-white border-b border-slate-200 h-auto lg:h-24 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 px-6 py-5 sticky top-0 z-20 w-full shrink-0">
        <div class="space-y-2">
            <nav class="flex items-center gap-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
                <span>/</span>
                <span>Settings</span>
                <span>/</span>
                <span class="text-slate-700">{{ $section['label'] }}</span>
            </nav>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-extrabold text-slate-900 leading-tight">{{ $section['label'] }}</h1>
                    <span class="relative inline-flex group">
                        <span tabindex="0" role="button" aria-label="About {{ $section['label'] }}" class="flex h-5 w-5 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-[11px] font-black text-blue-600 cursor-help">i</span>
                        <span class="pointer-events-none absolute left-0 top-7 z-[9999] hidden w-80 max-w-[calc(100vw-2rem)] rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-relaxed text-white shadow-xl group-hover:block group-focus-within:block normal-case tracking-normal">
                            {{ $section['description'] }}
                        </span>
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500 max-w-3xl">{{ $section['description'] }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <form id="settings-reset-form" method="POST" action="{{ route('settings.' . $section['route'] . '.reset') }}" onsubmit="return confirm('Reset {{ $section['label'] }} to defaults?')">
                @csrf
            </form>
            <button type="submit" form="settings-reset-form" @disabled(!$canUpdateSettings) class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 12a9 9 0 1 0 3-6.7M3 4v6h6"/>
                </svg>
                <span>Reset Defaults</span>
            </button>
            <button type="submit" form="settings-form" @disabled(!$canUpdateSettings) class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <path d="M17 21v-8H7v8M7 3v5h8"/>
                </svg>
                <span>Save Changes</span>
            </button>
        </div>
    </div>

    <div class="flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="p-6 md:p-8 space-y-6 max-w-7xl mx-auto">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($settingsErrors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
                    Please fix the highlighted fields and save again.
                </div>
            @endif

            @if(!empty($section['links']))
                <div class="flex flex-wrap gap-3">
                    @foreach($section['links'] as $link)
                        @if(Route::has($link['route']))
                            <a href="{{ route($link['route']) }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-xs font-bold text-blue-700 shadow-sm transition hover:bg-blue-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M5 12h14M13 5l7 7-7 7"/>
                                </svg>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <form id="settings-form" method="POST" action="{{ route('settings.' . $section['route'] . '.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                @foreach($section['groups'] as $group)
                    <section class="bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-visible">
                        <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $section['icon'] }}"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-extrabold text-slate-900">{{ $group['title'] }}</h2>
                                    <span class="relative inline-flex group">
                                        <span tabindex="0" role="button" aria-label="About {{ $group['title'] }}" class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-200 bg-white text-[11px] font-black text-slate-500 shadow-sm cursor-help">i</span>
                                        <span class="pointer-events-none absolute left-0 top-7 z-[9999] hidden w-80 max-w-[calc(100vw-2rem)] rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-relaxed text-white shadow-xl group-hover:block group-focus-within:block">
                                            {{ $group['description'] }}
                                        </span>
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $group['description'] }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-5 p-6">
                            @foreach($group['fields'] as $field)
                                @php
                                    $key = $field['key'];
                                    $type = $field['type'] ?? 'text';
                                    $fieldValue = old($key, $values[$key] ?? '');
                                    $wide = in_array($type, ['textarea', 'file'], true);
                                    $helpText = $fieldHelp($field);
                                @endphp

                                <div class="{{ $wide ? 'lg:col-span-2' : '' }}">
                                    @if($type === 'toggle')
                                        <label class="flex min-h-16 items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <span>
                                                <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                                    <span>{{ $field['label'] }}</span>
                                                    <span class="relative inline-flex group">
                                                        <span tabindex="0" role="button" onclick="event.preventDefault()" aria-label="About {{ $field['label'] }}" class="flex h-4 w-4 items-center justify-center rounded-full border border-blue-200 bg-white text-[10px] font-black text-blue-600 cursor-help">i</span>
                                                        <span class="pointer-events-none absolute left-0 top-6 z-[9999] hidden w-80 max-w-[calc(100vw-2rem)] rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-relaxed text-white shadow-xl group-hover:block group-focus-within:block">
                                                            {{ $helpText }}
                                                        </span>
                                                    </span>
                                                </span>
                                                <span class="mt-0.5 block text-xs text-slate-500">{{ $field['hint'] ?? 'Enable or disable this option.' }}</span>
                                            </span>
                                            <input type="checkbox" name="{{ $key }}" value="1" @checked((bool) $fieldValue) class="peer sr-only">
                                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-600 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span>
                                        </label>
                                    @else
                                        <label for="{{ $key }}" class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                            <span>{{ $field['label'] }}</span>
                                            <span class="relative inline-flex group">
                                                <span tabindex="0" role="button" aria-label="About {{ $field['label'] }}" class="flex h-4 w-4 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-[10px] font-black text-blue-600 cursor-help">i</span>
                                                <span class="pointer-events-none absolute left-0 top-6 z-[9999] hidden w-80 max-w-[calc(100vw-2rem)] rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-relaxed text-white shadow-xl group-hover:block group-focus-within:block">
                                                    {{ $helpText }}
                                                </span>
                                            </span>
                                        </label>

                                        @if($type === 'select')
                                            <select id="{{ $key }}" name="{{ $key }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                @foreach($field['options'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($type === 'textarea')
                                            <textarea id="{{ $key }}" name="{{ $key }}" rows="4" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">{{ $fieldValue }}</textarea>
                                        @elseif($type === 'file')
                                            <div class="mt-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                                @if($fieldValue)
                                                    <div class="mb-3 flex items-center gap-3">
                                                        <img src="{{ asset('storage/' . $fieldValue) }}" alt="{{ $field['label'] }}" class="h-14 w-14 rounded-lg object-contain bg-white border border-slate-200">
                                                        <span class="text-xs font-semibold text-slate-500">{{ $fieldValue }}</span>
                                                    </div>
                                                @endif
                                                <input id="{{ $key }}" name="{{ $key }}" type="file" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-blue-700">
                                            </div>
                                        @else
                                            <input id="{{ $key }}" name="{{ $key }}" type="{{ $type }}" value="{{ $fieldValue }}" @if($type === 'number') step="any" @endif class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @endif
                                    @endif

                                    @if($settingsErrors->has($key))
                                        <p class="mt-2 text-xs font-semibold text-red-600">{{ $settingsErrors->first($key) }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </form>
        </div>
    </div>
</x-layouts::app>
