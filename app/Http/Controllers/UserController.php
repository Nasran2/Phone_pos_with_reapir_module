<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'lorryAssignments']);

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if ($request->filled('role')) {
            $roleId = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        // Filter by Status
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('is_active', $status === 'active');
        }

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::all();
        $lorries = DB::table('lorries')->whereNull('deleted_at')->where('status', 'active')->orderBy('lorry_no')->get();

        return view('users.index', compact('users', 'roles', 'lorries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isLorryManager = Role::whereKey($request->input('role_id'))->where('name', 'Lorry Manager')->exists();
        $request->merge(['user_type' => $isLorryManager ? 'lorry' : 'office', 'lorry_role' => $isLorryManager ? 'lorry_manager' : null, 'lorry_id' => $isLorryManager ? $request->input('lorry_id') : null]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'user_type' => ['required', Rule::in(['office','store','lorry','distribution_supervisor'])],
            'lorry_id' => ['required_if:user_type,lorry', 'nullable', Rule::exists('lorries', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'lorry_role' => ['required_if:user_type,lorry', 'nullable', Rule::in(['lorry_manager','distribution_salesperson','lorry_driver','lorry_assistant','distribution_supervisor'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active'),
            'user_type' => $validated['user_type'],
            'default_lorry_id' => $validated['lorry_id'] ?? null,
        ]);

        $user->roles()->sync([$validated['role_id']]);
        $this->syncLorryAssignment($request, $user, $validated);

        // Log action
        ActivityLog::log('Create', 'User Management', "Created user: {$user->name} ({$user->username})");

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $isLorryManager = Role::whereKey($request->input('role_id'))->where('name', 'Lorry Manager')->exists();
        $request->merge(['user_type' => $isLorryManager ? 'lorry' : 'office', 'lorry_role' => $isLorryManager ? 'lorry_manager' : null, 'lorry_id' => $isLorryManager ? $request->input('lorry_id') : null]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'user_type' => ['required', Rule::in(['office','store','lorry','distribution_supervisor'])],
            'lorry_id' => ['required_if:user_type,lorry', 'nullable', Rule::exists('lorries', 'id')->where(fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))],
            'lorry_role' => ['required_if:user_type,lorry', 'nullable', Rule::in(['lorry_manager','distribution_salesperson','lorry_driver','lorry_assistant','distribution_supervisor'])],
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active'),
            'user_type' => $validated['user_type'],
            'default_lorry_id' => $validated['lorry_id'] ?? null,
        ]);

        $user->roles()->sync([$validated['role_id']]);
        $this->syncLorryAssignment($request, $user, $validated);

        // Log action
        ActivityLog::log('Update', 'User Management', "Updated user details for: {$user->name}");

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log action
        ActivityLog::log('Update', 'User Management', "Updated password for user: {$user->name}");

        return redirect()->route('users.index')->with('success', 'Password updated successfully.');
    }

    /**
     * Toggle status active/inactive.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot change your own status.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'Active' : 'Inactive';
        // Log action
        ActivityLog::log('Update', 'User Management', "Toggled status of user: {$user->name} to {$statusStr}");

        return redirect()->route('users.index')->with('success', "User status updated to {$statusStr}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete yourself.');
        }

        $userName = $user->name;
        $userUsername = $user->username;

        // Delete user roles relation first (done by cascade if migration allows, but Eloquent is safe)
        $user->roles()->detach();
        $user->delete();

        // Log action
        ActivityLog::log('Delete', 'User Management', "Deleted user: {$userName} ({$userUsername})");

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function syncLorryAssignment(Request $request, User $user, array $data): void
    {
        if (($data['user_type'] ?? null) !== 'lorry') {
            $user->lorryAssignments()->update(['is_active' => false]);
            return;
        }
        $user->lorryAssignments()->updateOrCreate(['lorry_id' => $data['lorry_id']], [
            'lorry_role' => $data['lorry_role'], 'is_primary' => true, 'is_active' => true,
            'active_from' => $request->input('active_from', today()->toDateString()), 'active_until' => $request->input('active_until'),
            'can_sell' => true, 'can_create_orders' => true,
            'can_collect' => true, 'can_add_expenses' => true,
            'can_view_cost' => $request->boolean('can_view_cost'), 'can_view_profit' => $request->boolean('can_view_profit'),
            'can_reconcile' => true, 'assigned_by' => auth()->id(),
        ]);
        $user->lorryAssignments()->where('lorry_id', '!=', $data['lorry_id'])->update(['is_primary' => false]);
    }
}
