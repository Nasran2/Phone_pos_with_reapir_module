<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')->with('permissions')->get();
        
        // Group permissions by their prefix/module to present a nice visual grid in UI
        $moduleLabels = [
            'repair' => 'Repair Management',
            'retail_pos' => 'Retail POS',
            'wholesale_pos' => 'Wholesale POS',
            'sales_return' => 'Sales Returns',
            'hold_sales' => 'Hold Sales',
            'stock' => 'Store Stock',
            'damaged_stock' => 'Damaged Stock',
            'activity_logs' => 'Activity Logs',
        ];
        $permissions = Permission::orderBy('name')->get()->groupBy(function($item) use ($moduleLabels) {
            $parts = explode('.', $item->name);
            return $moduleLabels[$parts[0] ?? ''] ?? ucwords(str_replace('_', ' ', $parts[0] ?? 'Other'));
        })->sortKeys();

        return view('roles.index', compact('roles', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        // Log activity
        ActivityLog::log('Create', 'User Management', "Created role: {$role->name}");

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        // Protect system default critical roles from name changes
        $isDefaultRole = in_array($role->name, ['Super Admin', 'Admin']) || $role->is_fixed;

        $rules = [
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];

        if (!$isDefaultRole) {
            $rules['name'] = ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'description' => $validated['description'] ?? '',
        ];

        if (!$isDefaultRole) {
            $updateData['name'] = $validated['name'];
        }

        $role->update($updateData);

        // Sync permissions
        // Note: Super Admin retains all permissions, but for custom/other roles sync what was selected
        if ($role->name === 'Super Admin') {
            // Force Super Admin to keep all permissions
            $allPerms = Permission::pluck('id')->toArray();
            $role->permissions()->sync($allPerms);
        } elseif (! $role->is_fixed) {
            $role->permissions()->sync($request->input('permissions', []));
        }

        // Log activity
        ActivityLog::log('Update', 'User Management', "Updated role details and permissions for: {$role->name}");

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting default roles
        if (! $role->is_deletable || in_array($role->name, ['Super Admin', 'Admin', 'Cashier', 'Sales Staff', 'Stock Manager', 'Accountant', 'Repair Technician'])) {
            return redirect()->route('roles.index')->with('error', "System default role '{$role->name}' cannot be deleted.");
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', "Role '{$role->name}' cannot be deleted because it is assigned to active users.");
        }

        $roleName = $role->name;

        // Detach permissions and delete
        $role->permissions()->detach();
        $role->delete();

        // Log activity
        ActivityLog::log('Delete', 'User Management', "Deleted role: {$roleName}");

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
