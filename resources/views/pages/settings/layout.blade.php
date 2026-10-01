<div class="lorry-settings grid w-full grid-cols-1 items-start gap-6 md:grid-cols-[240px_1fr]">
    <aside class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white p-3 shadow-xs">
        <p class="px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-wider text-black">Account Menu</p>
        <nav class="space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-3 text-xs font-bold transition {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white shadow-sm':'text-black hover:bg-slate-100' }}"><span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('profile.edit') ? 'bg-white/15':'bg-blue-50 text-blue-600' }}">👤</span>{{ __('Profile') }}</a>
            <a href="{{ route('security.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-3 text-xs font-bold transition {{ request()->routeIs('security.edit') ? 'bg-blue-600 text-white shadow-sm':'text-black hover:bg-slate-100' }}"><span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('security.edit') ? 'bg-white/15':'bg-blue-50 text-blue-600' }}">🔒</span>{{ __('Security') }}</a>
            <a href="{{ route('appearance.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-3 text-xs font-bold transition {{ request()->routeIs('appearance.edit') ? 'bg-blue-600 text-white shadow-sm':'text-black hover:bg-slate-100' }}"><span class="flex h-7 w-7 items-center justify-center rounded-lg {{ request()->routeIs('appearance.edit') ? 'bg-white/15':'bg-blue-50 text-blue-600' }}">◐</span>{{ __('Appearance') }}</a>
        </nav>
    </aside>
    <section class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-xs">
        <header class="flex items-center gap-3 border-b border-slate-100 px-6 py-5"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">⚙</div><div><h2 class="text-sm font-extrabold text-black">{{ $heading ?? '' }}</h2><p class="mt-0.5 text-[11px] font-medium text-black">{{ $subheading ?? '' }}</p></div></header>
        <div class="max-w-3xl p-6">{{ $slot }}</div>
    </section>
</div>
<style>
    .lorry-settings input:not([type=checkbox]):not([type=radio]),.lorry-settings select,.lorry-settings textarea{border:1.5px solid #cbd5e1!important;background:#fff!important;border-radius:.6rem!important;min-height:44px!important}
    .lorry-settings input:focus,.lorry-settings select:focus,.lorry-settings textarea:focus{border-color:#2563eb!important;box-shadow:0 0 0 3px rgba(37,99,235,.14)!important}
    .lorry-settings button[type=submit]{background:#2563eb!important;color:#fff!important;border-radius:.55rem!important;padding:.65rem 1.25rem!important;font-weight:700!important}
    .lorry-settings button[type=submit]:hover{background:#1d4ed8!important}
</style>
