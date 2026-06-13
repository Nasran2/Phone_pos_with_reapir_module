<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

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

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active'),
        ]);

        $user->roles()->sync([$validated['role_id']]);

        // Log action
        ActivityLog::log('Create', 'User Management', "Created user: {$user->name} ({$user->username})");

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active'),
        ]);

        $user->roles()->sync([$validated['role_id']]);

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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
}
