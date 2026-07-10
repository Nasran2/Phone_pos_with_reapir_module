<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = collect(config('settings_sections', []))
            ->flatMap(fn (array $section) => [
                'settings.' . $section['route'] . '.view',
                'settings.' . $section['route'] . '.update',
            ])
            ->values();

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissions = collect(config('settings_sections', []))
            ->flatMap(fn (array $section) => [
                'settings.' . $section['route'] . '.view',
                'settings.' . $section['route'] . '.update',
            ])
            ->values();

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
