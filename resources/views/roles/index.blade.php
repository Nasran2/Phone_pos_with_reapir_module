<x-layouts::app :title="__('Roles & Permissions')">
    <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 sticky top-0 z-20 w-full shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Roles</h2>
            <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                {{ $roles->count() }} System Roles
            </span>
        </div>
        <div>
            @if(auth()->user()->hasPermission('roles.create'))
            <button onclick="openCreateModal()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Create Role</span>
            </button>
            @endif
        </div>
    </div>

    <div class="p-6 md:p-8 space-y-6 flex-1 w-full overflow-y-auto bg-slate-50">
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

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($roles as $role)
            <div class="bg-white rounded-2xl border border-slate-200/65 shadow-xs flex flex-col justify-between overflow-hidden hover:shadow-md transition duration-200">
                <div class="p-6 space-y-4">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">{{ $role->name }}</h3>
                            <span class="inline-block mt-1 bg-slate-100 text-slate-650 font-bold px-2 py-0.5 rounded text-[10px]">
                                {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-1">
                            @if(auth()->user()->hasPermission('roles.edit'))
                            <button 
                                onclick="openEditModal({{ json_encode($role) }}, {{ json_encode($role->permissions->pluck('id')) }})"
                                class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition rounded-lg cursor-pointer"
                                title="Edit Role Permissions"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @endif

                            @if(auth()->user()->hasPermission('roles.delete') && !in_array($role->name, ['Super Admin', 'Admin', 'Cashier', 'Sales Staff', 'Stock Manager', 'Accountant']))
                            <form method="POST" action="{{ route('roles.destroy', $role->id) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    onclick="return confirmAction(event, 'Are you sure you want to delete role {{ $role->name }}?')"
                                    class="p-1.5 text-slate-400 hover:text-red-650 hover:bg-red-50 transition rounded-lg cursor-pointer"
                                    title="Delete Role"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    <!-- Role Description -->
                    <p class="text-xs text-slate-450 leading-relaxed min-h-[36px]">
                        {{ $role->description ?: 'No description provided.' }}
                    </p>

                    <!-- Permissions Summary -->
                    <div class="space-y-1.5 border-t border-slate-100 pt-3">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Privileges Summary</span>
                        <div class="flex flex-wrap gap-1">
                            @if($role->name === 'Super Admin')
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-bold rounded text-[9px] border border-blue-100">
                                    ALL PRIVILEGES ALLOWED
                                </span>
                            @else
                                @php $limit = 5; @endphp
                                @forelse($role->permissions->take($limit) as $perm)
                                    <span class="px-2 py-0.5 bg-slate-50 text-slate-600 font-medium rounded text-[9px] border border-slate-100">
                                        {{ str_replace('.', ': ', $perm->name) }}
                                    </span>
                                @empty
                                    <span class="text-[10px] text-slate-400 font-medium">No permissions assigned.</span>
                                @endforelse

                                @if($role->permissions->count() > $limit)
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 font-bold rounded text-[9px]">
                                        +{{ $role->permissions->count() - $limit }} more
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ROLE CREATION / EDIT MODAL -->
    <div id="roleModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col transition-all duration-300 scale-95 opacity-0 modal-container">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
                <h3 class="text-sm font-bold text-slate-850" id="modalTitle">Create System Role</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
            </div>

            <!-- Modal Form -->
            <form id="roleForm" method="POST" action="" class="flex-1 overflow-hidden flex flex-col">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <!-- Scrollable Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1 text-slate-800">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Role Identifier Name *</label>
                            <input type="text" name="name" id="role_name" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="e.g. Sales Executive">
                            <p class="text-[10px] text-slate-400 mt-1">Must be alphanumeric and unique.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1.5">Short Description</label>
                            <input type="text" name="description" id="role_description" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800" placeholder="Summarize role duties / operational access boundary...">
                        </div>
                    </div>

                    <!-- Permission Checkbox Grid -->
                    <div class="border-t border-slate-100 pt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wide">Assign Permission Privileges</h4>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="toggleAllCheckboxes(true)" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer">Select All</button>
                                <span class="text-slate-300 text-[10px]">|</span>
                                <button type="button" onclick="toggleAllCheckboxes(false)" class="text-[10px] font-bold text-slate-500 hover:underline cursor-pointer">Deselect All</button>
                            </div>
                        </div>

                        <!-- System Warning for Super Admin override -->
                        <div id="superadmin-warning" class="hidden p-3 bg-blue-50 rounded-xl text-[11px] text-blue-800 border border-blue-100">
                            <strong>Note:</strong> Super Admin role automatically bypasses all checks and retains root access. Individual privileges cannot be deselected.
                        </div>

                        <!-- Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($permissions as $moduleName => $modulePerms)
                            <div class="bg-slate-50/60 rounded-xl border border-slate-100 p-4 space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-200/50 pb-1.5">
                                    <h5 class="text-xs font-bold text-slate-700">{{ $moduleName }}</h5>
                                    <button type="button" onclick="toggleModuleCheckboxes(this, true)" class="text-[9px] font-bold text-blue-600 hover:underline cursor-pointer">All</button>
                                </div>
                                <div class="space-y-2">
                                    @foreach($modulePerms as $perm)
                                    @php
                                        $permissionAction = Str::of($perm->name)->after('.')->replace(['_', '.'], ' ')->title();
                                    @endphp
                                    <div class="flex items-start gap-2.5">
                                        <input 
                                            type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $perm->id }}" 
                                            id="perm_{{ $perm->id }}"
                                            class="perm-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4 mt-0.5"
                                        >
                                        <label for="perm_{{ $perm->id }}" class="text-xs text-slate-650 select-none cursor-pointer leading-tight">
                                            <span class="font-bold text-slate-800">{{ $permissionAction }}</span>
                                            <span class="block text-[9px] text-slate-400 mt-0.5 font-mono">({{ $perm->name }})</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 shrink-0">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 text-slate-500 hover:bg-slate-50 text-xs font-bold rounded-lg transition duration-150 cursor-pointer">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition duration-150 shadow-sm cursor-pointer">Save Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal scripting -->
    <script>
        function openModal() {
            const modal = document.getElementById('roleModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            setTimeout(() => {
                const container = modal.querySelector('.modal-container');
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('roleModal');
            const container = modal.querySelector('.modal-container');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function openCreateModal() {
            // Reset fields
            document.getElementById('modalTitle').innerText = 'Create System Role';
            document.getElementById('roleForm').action = "{{ route('roles.store') }}";
            document.getElementById('formMethod').value = 'POST';
            
            document.getElementById('role_name').value = '';
            document.getElementById('role_name').disabled = false;
            document.getElementById('role_description').value = '';
            
            document.getElementById('superadmin-warning').classList.add('hidden');
            
            // Clear checks
            toggleAllCheckboxes(false);
            enableAllCheckboxes(true);
            
            openModal();
        }

        function openEditModal(role, checkedPermissions) {
            document.getElementById('modalTitle').innerText = `Edit Role: ${role.name}`;
            document.getElementById('roleForm').action = `/roles/${role.id}`;
            document.getElementById('formMethod').value = 'PUT';
            
            document.getElementById('role_name').value = role.name;
            document.getElementById('role_description').value = role.description || '';
            
            // Disable name modification for critical system roles
            const isSystemRole = ['Super Admin', 'Admin'].includes(role.name);
            document.getElementById('role_name').disabled = isSystemRole;

            // Clear and set checks
            toggleAllCheckboxes(false);
            
            if (role.name === 'Super Admin') {
                document.getElementById('superadmin-warning').classList.remove('hidden');
                toggleAllCheckboxes(true);
                enableAllCheckboxes(false);
            } else {
                document.getElementById('superadmin-warning').classList.add('hidden');
                enableAllCheckboxes(true);
                
                checkedPermissions.forEach(id => {
                    const checkbox = document.getElementById(`perm_${id}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            openModal();
        }

        function toggleAllCheckboxes(status) {
            const checkboxes = document.querySelectorAll('.perm-checkbox');
            checkboxes.forEach(c => {
                if (!c.disabled) c.checked = status;
            });
        }

        function toggleModuleCheckboxes(button, status) {
            const moduleContainer = button.closest('div').nextElementSibling;
            const checkboxes = moduleContainer.querySelectorAll('.perm-checkbox');
            checkboxes.forEach(c => {
                if (!c.disabled) c.checked = status;
            });
        }

        function enableAllCheckboxes(status) {
            const checkboxes = document.querySelectorAll('.perm-checkbox');
            checkboxes.forEach(c => {
                c.disabled = !status;
            });
        }
    </script>
</x-layouts::app>
