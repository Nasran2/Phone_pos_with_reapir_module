<?php

namespace Database\Seeders;

use App\Support\Features;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LorryManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $permissions = ['my_lorry.dashboard', 'my_lorry.stock', 'my_lorry.stock_history', 'my_lorry.orders.view', 'my_lorry.orders.create', 'my_lorry.orders.edit', 'my_lorry.orders.cancel', 'my_lorry.sales.view', 'my_lorry.sales.create', 'my_lorry.sales.return', 'my_lorry.customers.view', 'my_lorry.customers.create', 'my_lorry.customers.edit', 'my_lorry.collections.view', 'my_lorry.collections.create', 'my_lorry.expenses.view', 'my_lorry.expenses.create', 'my_lorry.expenses.edit', 'my_lorry.trips.view', 'my_lorry.trips.update', 'my_lorry.reconciliation.view', 'my_lorry.reconciliation.submit', 'my_lorry.reports.view', 'my_lorry.reports.export_pdf', 'my_lorry.reports.export_excel', 'profile.view', 'profile.update'];
        $permissions = array_filter($permissions, fn ($name) => Features::permissionEnabled($name));
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('roles')->updateOrInsert(['name' => 'Lorry Manager'], ['slug' => 'lorry-manager', 'guard_name' => 'web', 'description' => 'Fixed system role for managing one assigned lorry.', 'is_system_role' => true, 'is_fixed' => true, 'is_deletable' => false, 'is_renamable' => false, 'created_at' => $now, 'updated_at' => $now]);
        $roleId = DB::table('roles')->where('name', 'Lorry Manager')->value('id');
        foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        DB::table('lorry_user_assignments')->whereIn('user_id', DB::table('user_roles')->where('role_id', $roleId)->select('user_id'))
            ->update(['lorry_role' => 'lorry_manager', 'can_sell' => true, 'can_create_orders' => true, 'can_collect' => true, 'can_add_expenses' => true, 'can_reconcile' => true, 'updated_at' => $now]);
    }
}
