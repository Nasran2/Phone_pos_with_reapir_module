<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'Lorry Manager')->value('id');
        if (! $roleId) return;

        foreach (['wholesale_pos.view', 'wholesale_pos.create_sale', 'customers.create'] as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            if ($permissionId) DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'Lorry Manager')->value('id');
        if (! $roleId) return;
        $permissionIds = DB::table('permissions')->whereIn('name', ['wholesale_pos.view', 'wholesale_pos.create_sale', 'customers.create'])->pluck('id');
        DB::table('role_permissions')->where('role_id', $roleId)->whereIn('permission_id', $permissionIds)->delete();
    }
};
