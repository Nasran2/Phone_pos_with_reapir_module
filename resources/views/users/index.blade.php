<x-layouts::app :title="__('Users Management')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Users</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $users->total() }} Total
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('users.create'))
            <button onclick="openModal('addUserModal')" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add User</span>
            </button>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto">
        <!-- Success & Error Alert Messages -->
        @if(session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center justify-between text-emerald-800 text-xs font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center justify-between text-red-800 text-xs font-medium">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800 cursor-pointer">✕</button>
        </div>
        @endif

        @if ($errors->any())
        <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs font-medium space-y-1">
            <div class="font-bold flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Validation Errors:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Filter Bar Card -->
        <div class="bg-white rounded-xl border border-slate-200/60 p-4 shadow-xs">
            <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search bar -->
                <div class="relative">
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search name, email, phone..." 
                        class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                </div>

                <!-- Role Filter -->
                <div>
                    <select 
                        name="role" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition"
                    >
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select 
                        name="status" 
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 transition"
                    >
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-950 text-white font-bold rounded-lg text-xs transition duration-150 cursor-pointer">
                        Filter
                    </button>
                    <a href="{{ route('users.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs text-center transition duration-150 cursor-pointer">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60 text-slate-650 text-[11px] font-bold uppercase tracking-wider">
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Username</th>
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Created Date</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                        @forelse($users as $user)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-blue-500 text-white font-bold flex items-center justify-center text-xs">
                                        {{ $user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $user->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-[11px] text-slate-500">
                                {{ $user->username }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $user->phone ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 font-semibold rounded-md text-[10px]">
                                    {{ $user->roles->first()->name ?? 'No Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if(auth()->user()->hasPermission('users.edit'))
                                <form method="POST" action="{{ route('users.toggle-status', $user->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button 
                                        type="submit" 
                                        class="cursor-pointer focus:outline-none"
                                        title="Click to toggle status"
                                    >
                                        @if($user->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Inactive
                                        </span>
                                        @endif
                                    </button>
                                </form>
                                @else
                                    @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Inactive
                                    </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ $user->created_at ? $user->created_at->format('M d, Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->hasPermission('users.edit'))
                                    <!-- Edit details -->
                                    <button 
                                        onclick="openEditModal({{ json_encode($user) }}, {{ $user->roles->first()->id ?? 'null' }})"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                        title="Edit Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Reset password -->
                                    <button 
                                        onclick="openPasswordModal({{ json_encode($user) }})"
                                        class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition rounded-lg cursor-pointer"
                                        title="Change Password"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </button>
                                    @endif

                                    @if(auth()->user()->hasPermission('users.delete'))
                                    <!-- Delete form -->
                                    <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            onclick="return confirm('Are you sure you want to delete user {{ $user->name }}?')"
                                            class="p-1.5 text-slate-400 hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                            title="Delete User"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                <span class="font-bold">No users found matching query parameters.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination block -->
            @if($users->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200/60">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addUserModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850">Add New User Account</h3>
                <button onclick="closeModal('addUserModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Full Name *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="e.g. John Doe">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Username *</label>
                        <input type="text" name="username" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="e.g. johndoe">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Email Address *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="name@company.com">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Phone Number</label>
                        <input type="text" name="phone" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="e.g. 0771234567">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Password *</label>
                        <input type="password" name="password" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Assign System Role *</label>
                        <select name="role_id" required onchange="toggleLorryManagerCard(this, 'addLorryManagerCard')" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-800 bg-white">
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" data-lorry-manager="{{ $role->name === 'Lorry Manager' ? '1' : '0' }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" id="addUserActive" value="1" checked class="rounded border-slate-350 text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <label for="addUserActive" class="text-xs font-bold text-slate-700 select-none cursor-pointer">Activate user account immediately</label>
                </div>

                <div id="addLorryManagerCard" class="hidden rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <h4 class="text-sm font-black text-slate-900">Lorry Manager Assignment</h4><p class="mt-1 text-xs text-slate-500">This user will only be able to manage the selected lorry.</p>
                    <label class="mt-4 block text-[10px] font-bold uppercase text-slate-500">Assigned Lorry *<select name="lorry_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Select active lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} — {{ $lorry->name }}</option>@endforeach</select></label>
                    <div class="mt-4 flex flex-wrap gap-3 text-xs">@foreach(['can_view_cost'=>'View cost','can_view_profit'=>'View profit','can_reconcile'=>'Submit reconciliation'] as $key=>$label)<label><input type="checkbox" name="{{ $key }}" value="1" class="rounded"> {{ $label }}</label>@endforeach</div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER DETAILS MODAL -->
    <div id="editUserModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850">Edit User Profile Details</h3>
                <button onclick="closeModal('editUserModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form id="editUserForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Full Name *</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Username *</label>
                        <input type="text" name="username" id="edit_username" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Email Address *</label>
                        <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Assign System Role *</label>
                    <select name="role_id" id="edit_role_id" required onchange="toggleLorryManagerCard(this, 'editLorryManagerCard')" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 text-slate-850 bg-white">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" data-lorry-manager="{{ $role->name === 'Lorry Manager' ? '1' : '0' }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-350 text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <label for="edit_is_active" class="text-xs font-bold text-slate-700 select-none cursor-pointer">Keep this account active</label>
                </div>

                <div id="editLorryManagerCard" class="hidden rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <h4 class="text-sm font-black text-slate-900">Lorry Manager Assignment</h4><p class="mt-1 text-xs text-slate-500">This user will only be able to manage the selected lorry.</p>
                    <label class="mt-4 block text-[10px] font-bold uppercase text-slate-500">Assigned Lorry *<select name="lorry_id" id="edit_lorry_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option value="">Select active lorry</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} — {{ $lorry->name }}</option>@endforeach</select></label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PASSWORD UPDATE MODAL -->
    <div id="passwordUserModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full overflow-hidden transition-all duration-300 scale-95 opacity-0 modal-container">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 class="text-sm font-bold text-slate-850">Reset Password</h3>
                <button onclick="closeModal('passwordUserModal')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>
            <form id="passwordUserForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-xs text-slate-600 mb-2">
                    Updating password for user: <span class="font-bold text-slate-800" id="password_user_name"></span>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">New Password *</label>
                    <input type="password" name="password" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-850" placeholder="Minimum 8 characters">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-850" placeholder="Confirm new password">
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeModal('passwordUserModal')" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer font-bold">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal helper scripts -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger animation
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openEditModal(user, roleId) {
            // Populate fields
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role_id').value = roleId || '';
            document.getElementById('edit_is_active').checked = !!user.is_active;
            const assignment = (user.lorry_assignments || []).find(item => item.is_primary) || (user.lorry_assignments || [])[0];
            document.getElementById('edit_lorry_id').value = assignment ? assignment.lorry_id : '';
            toggleLorryManagerCard(document.getElementById('edit_role_id'), 'editLorryManagerCard');
            
            // Set form route
            document.getElementById('editUserForm').action = `/users/${user.id}`;
            
            openModal('editUserModal');
        }

        function toggleLorryManagerCard(select, cardId) {
            const isLorryManager = select.selectedOptions[0]?.dataset.lorryManager === '1';
            const card = document.getElementById(cardId);
            card.classList.toggle('hidden', !isLorryManager);
            card.querySelector('select[name="lorry_id"]').required = isLorryManager;
        }

        function openPasswordModal(user) {
            // Set label
            document.getElementById('password_user_name').innerText = user.name;
            // Set form route
            document.getElementById('passwordUserForm').action = `/users/${user.id}/password`;
            
            openModal('passwordUserModal');
        }
    </script>
</x-layouts::app>
