<x-layouts::app :title="__('System Permissions Map')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">System Permissions Map</h2>
            <span class="text-xs bg-slate-100 text-slate-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-900/30 dark:text-slate-300">
                {{ \App\Models\Permission::count() }} Unique Codes
            </span>
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-xl p-4 text-xs text-blue-800 border border-blue-100 max-w-4xl">
            <div class="font-bold flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Administrative Notice:</span>
            </div>
            <span>Permissions are static guard codes mapped directly to system functions. Modifying these codes is restricted as it requires corresponding code updates. To manage which role has what access, please edit the role definitions directly on the <a href="{{ route('roles.index') }}" class="font-bold underline hover:text-blue-900">Roles Page</a>.</span>
        </div>

        <!-- Permissions Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl">
            @foreach($permissions as $moduleName => $modulePerms)
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Header -->
                    <div class="bg-slate-50/70 border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wide">{{ $moduleName }}</h4>
                        <span class="text-[10px] bg-slate-150 text-slate-650 px-2 py-0.5 rounded-md font-bold">
                            {{ $modulePerms->count() }} Codes
                        </span>
                    </div>

                    <!-- Body -->
                    <div class="p-5 space-y-3.5">
                        @foreach($modulePerms as $perm)
                        @php
                            $permissionAction = Str::of($perm->name)->after('.')->replace('_', ' ')->title();
                        @endphp
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">
                                    {{ $permissionAction }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block leading-none">
                                    {{ $perm->name }}
                                </span>
                            </div>
                            <span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-mono select-all">
                                web
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</x-layouts::app>
