<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-100 antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-slate-100 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="mb-1 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/20">
                        <x-app-logo-icon class="size-7 fill-current text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ \App\Support\SystemSettings::get('system_name','Phone Shop POS','business_settings') }} · Secure Account Access</p>
        </div>

        <style>input:not([type=checkbox]):not([type=radio]){border:1.5px solid #cbd5e1!important;background:#fff!important;border-radius:.6rem!important;min-height:46px!important}input:focus{border-color:#2563eb!important;box-shadow:0 0 0 3px rgba(37,99,235,.14)!important}button[type=submit]{background:#2563eb!important;color:#fff!important;border-radius:.6rem!important;min-height:46px!important;font-weight:700!important}button[type=submit]:hover{background:#1d4ed8!important}</style>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
