<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'tax_management.view',
            'tax_dashboard.view',
            'tax_transactions.view',
            'tax_payments.view',
            'tax_payments.create',
            'tax_payments.approve',
            'tax_payments.reverse',
            'tax_periods.view',
            'tax_periods.close',
            'tax_periods.reopen',
            'tax_adjustments.create',
            'tax_adjustments.approve',
            'tax_reports.view',
            'tax_reports.export',
            'tax_settings.manage',
            'tax_documents.view',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $superAdminRoleId = DB::table('roles')->where('name', 'Super Admin')->value('id');
        if ($superAdminRoleId) {
            $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminRoleId, 'permission_id' => $permissionId]
                );
            }
        }
    }
}
