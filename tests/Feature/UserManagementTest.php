<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('guest users are redirected to login for user management pages', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('roles.index'))->assertRedirect(route('login'));
    $this->get(route('permissions.index'))->assertRedirect(route('login'));
    $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
});

test('unauthorized users without permissions are blocked from user management pages', function () {
    // Create a regular user who has NO roles/permissions
    $user = User::factory()->create();
    
    $this->actingAs($user)->get(route('users.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('roles.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('permissions.index'))->assertStatus(403);
    $this->actingAs($user)->get(route('activity-logs.index'))->assertStatus(403);
});

test('super admin can access all user management pages', function () {
    $user = createUserWithRole('Super Admin');
    
    $this->actingAs($user)->get(route('users.index'))->assertOk();
    $this->actingAs($user)->get(route('roles.index'))->assertOk();
    $this->actingAs($user)->get(route('permissions.index'))->assertOk();
    $this->actingAs($user)->get(route('activity-logs.index'))->assertOk();
});

test('administrator can create new users and assign roles', function () {
    $admin = createUserWithRole('Super Admin');
    
    // Seed default role to assign
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Test Cashier Role',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Cashier Test',
        'username' => 'newcashiertest',
        'email' => 'newcashier@example.com',
        'phone' => '0779999999',
        'password' => 'secretpassword123',
        'role_id' => $roleId,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('users.index'));
    
    $this->assertDatabaseHas('users', [
        'name' => 'New Cashier Test',
        'username' => 'newcashiertest',
        'email' => 'newcashier@example.com',
        'phone' => '0779999999',
        'is_active' => true,
    ]);

    $user = User::where('username', 'newcashiertest')->first();
    expect($user->roles->first()->id)->toBe($roleId);

    // Verify activity log was recorded
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'Create',
        'module' => 'User Management',
        'description' => 'Created user: New Cashier Test (newcashiertest)',
    ]);
});

test('administrator can update users and toggle active status', function () {
    $admin = createUserWithRole('Super Admin');
    
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Test Staff Role',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::factory()->create(['is_active' => true]);
    DB::table('user_roles')->insert([
        'user_id' => $user->id,
        'role_id' => $roleId,
    ]);

    // Update details
    $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
        'name' => 'Updated User Name',
        'username' => 'updatedusername',
        'email' => 'updatedemail@example.com',
        'phone' => '0778888888',
        'role_id' => $roleId,
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('users.index'));
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated User Name',
        'username' => 'updatedusername',
        'email' => 'updatedemail@example.com',
    ]);

    // Toggle active status
    $response = $this->actingAs($admin)->patch(route('users.toggle-status', $user->id));
    $response->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->is_active)->toBeFalse();

    // Verify status change logged
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'Update',
        'module' => 'User Management',
        'description' => 'Toggled status of user: Updated User Name to Inactive',
    ]);
});

test('administrator can create role and assign permissions', function () {
    $admin = createUserWithRole('Super Admin');

    // Create a mock permission
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'test_module.view',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('roles.store'), [
        'name' => 'New Manager Role',
        'description' => 'Role for testing module manager functionality',
        'permissions' => [$permissionId],
    ]);

    $response->assertRedirect(route('roles.index'));

    $this->assertDatabaseHas('roles', [
        'name' => 'New Manager Role',
        'description' => 'Role for testing module manager functionality',
    ]);

    $role = Role::where('name', 'New Manager Role')->first();
    expect($role->permissions->contains($permissionId))->toBeTrue();

    // Verify activity log was recorded
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'Create',
        'module' => 'User Management',
        'description' => 'Created role: New Manager Role',
    ]);
});

test('system prevents deleting default protected roles', function () {
    $admin = createUserWithRole('Super Admin');

    // Super Admin role
    $superAdminRole = Role::where('name', 'Super Admin')->first();
    
    if ($superAdminRole) {
        $response = $this->actingAs($admin)->delete(route('roles.destroy', $superAdminRole->id));
        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error');
        
        // Ensure role was NOT deleted
        $this->assertDatabaseHas('roles', [
            'id' => $superAdminRole->id,
            'name' => 'Super Admin',
        ]);
    }
});
