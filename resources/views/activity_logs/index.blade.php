<x-layouts::app :title="__('Activity Logs')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Activity Logs</h2>
            <span class="text-xs bg-slate-100 text-slate-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-slate-900/30 dark:text-blue-300">
                {{ $logs->total() }} Total Actions Logged
            </span>
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto">
        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search logs (action, description, IP)..." 
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Module Filter -->
                <div>
                    <select 
                        name="module" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>
                                {{ $module }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter -->
                <div>
                    <select 
                        name="user_id" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition bg-white"
                    >
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-950 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Logs Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Module</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">IP Address</th>
                            <th class="px-6 py-4">Browser/Device</th>
                            <th class="px-6 py-4">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                @if($log->user)
                                <div class="flex items-center gap-2.5">
                                    <div class="h-7 w-7 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-[10px]">
                                        {{ $log->user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $log->user->name }}</div>
                                        <div class="text-[9px] text-slate-400 font-mono">@johndoe</div>
                                    </div>
                                </div>
                                @else
                                <span class="text-slate-400 font-medium italic">System Task</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $actionColors = [
                                        'Create' => 'bg-emerald-100 text-emerald-800',
                                        'Update' => 'bg-amber-100 text-amber-800',
                                        'Delete' => 'bg-red-100 text-red-800',
                                        'Login' => 'bg-blue-100 text-blue-800',
                                        'Logout' => 'bg-slate-150 text-slate-800',
                                    ];
                                    $badgeColor = $actionColors[$log->action] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="px-2 py-0.5 font-bold rounded-full text-[9px] {{ $badgeColor }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-700">
                                {{ $log->module }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 max-w-sm truncate" title="{{ $log->description }}">
                                {{ $log->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-[10px] text-slate-500">
                                {{ $log->ip_address ?? '::1' }}
                            </td>
                            <td class="px-6 py-4 text-slate-450 text-[10px]" title="{{ $log->user_agent }}">
                                @php
                                    $ua = $log->user_agent;
                                    $os = 'Unknown OS';
                                    $browser = 'Unknown Browser';
                                    
                                    if (strpos($ua, 'Macintosh') !== false) $os = 'macOS';
                                    elseif (strpos($ua, 'Windows') !== false) $os = 'Windows';
                                    elseif (strpos($ua, 'Linux') !== false) $os = 'Linux';
                                    elseif (strpos($ua, 'iPhone') !== false) $os = 'iOS';
                                    elseif (strpos($ua, 'Android') !== false) $os = 'Android';

                                    if (strpos($ua, 'Chrome') !== false) $browser = 'Chrome';
                                    elseif (strpos($ua, 'Safari') !== false) $browser = 'Safari';
                                    elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
                                    elseif (strpos($ua, 'Edge') !== false) $browser = 'Edge';
                                @endphp
                                <span>{{ $browser }} on {{ $os }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 font-medium">
                                {{ $log->created_at ? $log->created_at->format('M d, Y H:i:s') : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No activity logs recorded.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            @if($logs->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</x-layouts::app>
