<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Support\Features;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Expense Categories
        $categories = [
            'Rent', 'Salary', 'Transport', 'Electricity', 'Internet',
            'Repair & Maintenance', 'Office Expenses', 'Marketing', 'Staff Food', 'Other',
        ];
        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat]);
        }

        // 2. Default Accounts
        $accounts = [
            // Assets
            ['account_code' => '1001', 'account_name' => 'Cash in Hand', 'account_type' => 'Asset'],
            ['account_code' => '1002', 'account_name' => 'Bank Account', 'account_type' => 'Asset'],
            ['account_code' => '1003', 'account_name' => 'Customer Receivables', 'account_type' => 'Asset'],
            ['account_code' => '1004', 'account_name' => 'Stock Inventory', 'account_type' => 'Asset'],

            // Liabilities
            ['account_code' => '2001', 'account_name' => 'Supplier Payables', 'account_type' => 'Liability'],
            ['account_code' => '2002', 'account_name' => 'Loan Payable', 'account_type' => 'Liability'],

            // Equity
            ['account_code' => '3001', 'account_name' => 'Owner Capital', 'account_type' => 'Equity'],
            ['account_code' => '3002', 'account_name' => 'Owner Drawings', 'account_type' => 'Equity'],

            // Income
            ['account_code' => '4001', 'account_name' => 'Retail Sales Income', 'account_type' => 'Income'],
            ['account_code' => '4002', 'account_name' => 'Wholesale Sales Income', 'account_type' => 'Income'],
            ['account_code' => '4003', 'account_name' => 'Other Income', 'account_type' => 'Income'],

            // Expenses
            ['account_code' => '5001', 'account_name' => 'Rent Expense', 'account_type' => 'Expense'],
            ['account_code' => '5002', 'account_name' => 'Salary Expense', 'account_type' => 'Expense'],
            ['account_code' => '5003', 'account_name' => 'Transport Expense', 'account_type' => 'Expense'],
            ['account_code' => '5004', 'account_name' => 'Electricity Expense', 'account_type' => 'Expense'],
            ['account_code' => '5005', 'account_name' => 'Internet Expense', 'account_type' => 'Expense'],
            ['account_code' => '5006', 'account_name' => 'Marketing Expense', 'account_type' => 'Expense'],
            ['account_code' => '5007', 'account_name' => 'Other Expense', 'account_type' => 'Expense'],
        ];

        foreach ($accounts as $acc) {
            if (! Features::wholesaleEnabled() && $acc['account_code'] === '4002') {
                continue;
            }
            Account::firstOrCreate(['account_code' => $acc['account_code']], [
                'account_name' => $acc['account_name'],
                'account_type' => $acc['account_type'],
                'opening_balance' => 0,
                'current_balance' => 0,
                'status' => true,
            ]);
        }
    }
}
