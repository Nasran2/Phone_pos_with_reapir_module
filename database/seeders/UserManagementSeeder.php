<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Grouped Permissions list
        $permissions = [
            // Dashboard
            'dashboard.view',
            // POS
            'retail_pos.view', 'retail_pos.create_sale', 'retail_pos.discount', 'retail_pos.hold_bill',
            'wholesale_pos.view', 'wholesale_pos.create_sale',
            // Products
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            'units.view', 'units.create', 'units.edit', 'units.delete',
            // Purchase
            'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.delete',
            'purchase.receive', 'purchase.return', 'purchase.print', 'purchase.download',
            // Sales
            'sales.view', 'sales.edit', 'sales.delete', 'sales.return',
            'sales.invoice_view', 'sales.print', 'sales.pdf', 'sales.whatsapp_share', 'sales.edit_payment',
            'sales_return.view', 'sales_return.create', 'sales_return.delete',
            'hold_sales.view', 'hold_sales.resume', 'hold_sales.delete',
            // Customers
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.payment', 'customers.statement',
            // Suppliers
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.payment', 'suppliers.statement',
            // Store Stock
            'stock.view', 'stock.adjust', 'stock.transfer', 'stock.history',
            'stock.adjustment.view', 'stock.adjustment.create', 'stock.adjustment.edit', 'stock.adjustment.delete',
            'damaged_stock.view', 'damaged_stock.create', 'damaged_stock.edit', 'damaged_stock.delete', 'damaged_stock.return',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            // Accounting
            'accounting.view', 'cashbook.view', 'bank.view', 't_account.view',
            // Reports
            'reports.view', 'sales_report.view', 'purchase_report.view', 'stock_report.view', 'profit_loss.view',
            // Settings
            'settings.view', 'settings.update',
            // User Management
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view',
            // Activity Logs
            'activity_logs.view',
        ];

        // Clear existing permissions and roles in dependency order (cross-db compatible)
        DB::table('role_permissions')->delete();
        DB::table('user_roles')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();

        // Insert permissions
        $permissionIds = [];
        foreach ($permissions as $permName) {
            $id = DB::table('permissions')->insertGetId([
                'name' => $permName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $permissionIds[$permName] = $id;
        }

        // 2. Roles with descriptions
        $roles = [
            'Super Admin' => [
                'desc' => 'Full access to all system modules and settings.',
                'perms' => array_keys($permissionIds),
            ],
            'Admin' => [
                'desc' => 'Administrative access, manages users, products and settings.',
                'perms' => array_keys($permissionIds),
            ],
            'Cashier' => [
                'desc' => 'Access to retail & wholesale POS billing and sales view.',
                'perms' => [
                    'dashboard.view', 'retail_pos.view', 'retail_pos.create_sale', 'retail_pos.discount', 
                    'retail_pos.hold_bill', 'wholesale_pos.view', 'wholesale_pos.create_sale',
                    'sales.view', 'sales.invoice_view', 'sales.print', 'sales.edit_payment', 'sales.return',
                    'sales_return.view', 'sales_return.create', 'hold_sales.view', 'hold_sales.resume',
                    'customers.view', 'customers.create', 'customers.edit'
                ]
            ],
            'Stock Manager' => [
                'desc' => 'Access to product stock, adjustments, purchases and transfers.',
                'perms' => [
                    'dashboard.view', 'products.view', 'products.create', 'products.edit', 'products.delete', 
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
                    'units.view', 'units.create', 'units.edit', 'units.delete',
                    'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.delete',
                    'purchase.receive', 'purchase.return', 'purchase.print', 'purchase.download',
                    'suppliers.view', 'suppliers.statement',
                    'stock.view', 'stock.adjust', 'stock.transfer', 'stock.history',
                    'stock.adjustment.view', 'stock.adjustment.create', 'stock.adjustment.edit', 'stock.adjustment.delete',
                    'damaged_stock.view', 'damaged_stock.create', 'damaged_stock.edit', 'damaged_stock.delete', 'damaged_stock.return'
                ]
            ],
            'Accountant' => [
                'desc' => 'Access to expenses, banking, cashbook, reports and general ledger.',
                'perms' => [
                    'dashboard.view', 'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 
                    'accounting.view', 'cashbook.view', 'bank.view', 't_account.view', 'reports.view', 
                    'sales_report.view', 'purchase_report.view', 'stock_report.view', 'profit_loss.view',
                    'customers.view', 'customers.statement', 'customers.payment',
                    'suppliers.view', 'suppliers.statement', 'suppliers.payment'
                ]
            ],
            'Sales Staff' => [
                'desc' => 'Access to billing and viewing sales ledger records.',
                'perms' => [
                    'dashboard.view', 'retail_pos.view', 'retail_pos.create_sale', 'retail_pos.hold_bill',
                    'sales.view', 'sales.invoice_view', 'sales.print', 'sales.whatsapp_share',
                    'sales_return.view', 'hold_sales.view', 'hold_sales.resume'
                ]
            ],
        ];

        // Insert roles and map permissions
        $roleIds = [];
        foreach ($roles as $roleName => $roleData) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleName,
                'description' => $roleData['desc'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $roleIds[$roleName] = $roleId;

            // Map permissions to role
            foreach ($roleData['perms'] as $permName) {
                if (isset($permissionIds[$permName])) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionIds[$permName],
                    ]);
                }
            }
        }

        // 3. Demo Users
        $demoUsers = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@twinsofte.com',
                'phone' => '0771112222',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Super Admin',
            ],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@twinsofte.com',
                'phone' => '0773334444',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Admin',
            ],
            [
                'name' => 'Cashier User',
                'username' => 'cashier',
                'email' => 'cashier@twinsofte.com',
                'phone' => '0775556666',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Cashier',
            ],
            [
                'name' => 'Stock Manager',
                'username' => 'stockmanager',
                'email' => 'stockmanager@twinsofte.com',
                'phone' => '0777778888',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Stock Manager',
            ],
            [
                'name' => 'Accountant User',
                'username' => 'accountant',
                'email' => 'accountant@twinsofte.com',
                'phone' => '0779990000',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Accountant',
            ],
            [
                'name' => 'Sales Staff User',
                'username' => 'salesstaff',
                'email' => 'salesstaff@twinsofte.com',
                'phone' => '0771234567',
                'password' => Hash::make('password'),
                'is_active' => true,
                'role' => 'Sales Staff',
            ],
        ];

        foreach ($demoUsers as $user) {
            // Delete existing user if exists (to avoid duplicate key collisions)
            DB::table('user_roles')->whereIn('user_id', function ($query) use ($user) {
                $query->select('id')->from('users')->where('username', $user['username']);
            })->delete();

            DB::table('users')->where('username', $user['username'])->delete();

            $userId = DB::table('users')->insertGetId([
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'password' => $user['password'],
                'is_active' => $user['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign role
            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleIds[$user['role']],
            ]);
        }
    }
}
