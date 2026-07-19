<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDatabaseForProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all operational demo data from the database to prepare for production.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('This will permanently delete all operational data (sales, products, customers, etc).');

        $tablesToTruncate = [
            // Sales & Purchases
            'sales', 'sale_items', 'sale_payments', 'sale_returns', 'sale_return_items', 'sale_documents',
            'purchases', 'purchase_items', 'purchase_payments', 'purchase_returns', 'purchase_return_items', 'purchase_documents',
            'hold_sales', 'hold_sale_items',

            // Customers & Suppliers
            'customers', 'customer_transactions', 'customer_payments', 'customer_opening_balances', 'customer_payment_allocations', 'customer_due_summaries',
            'suppliers', 'supplier_transactions', 'supplier_payments', 'supplier_opening_balances', 'supplier_payment_allocations', 'supplier_due_summaries',

            // Inventory
            'products', 'product_variants', 'product_prices', 'product_store_stock', 'product_location_stock', 'product_images', 'product_serials', 'product_warranties', 'product_daily_summaries',
            'stock_adjustments', 'stock_adjustment_items', 'stock_transfers', 'stock_transfer_items', 'damaged_stocks', 'stock_histories', 'stock_movements', 'low_stock_alerts',

            // Repairs
            'repair_jobs', 'repair_items', 'repair_payments', 'repair_parts', 'repair_devices', 'repair_status_logs', 'repair_activity_logs', 'repair_assignments', 'repair_notes', 'repair_warranties', 'repair_checklists', 'repair_charge_items', 'repair_technicians', 'repair_images',

            // Distribution (Lorries)
            'lorries', 'lorry_user_assignments', 'lorry_stocks', 'distribution_orders', 'distribution_order_items', 'distribution_trips', 'lorry_collections', 'lorry_reconciliations', 'lorry_sale_returns', 'lorry_stock_transfers', 'lorry_stock_transfer_items',

            // Finance & Accounting
            'accounts', 'bank_accounts', 'journal_entries', 'journal_entry_items', 'expenses', 'expense_categories', 'expense_documents', 'cheques', 'cheque_reminders', 'cheque_status_logs', 'daily_closings', 'daily_summaries', 'tax_payments', 'tax_transactions', 'tax_payment_allocations', 'tax_adjustments', 'tax_period_balances', 'petty_cash_transactions', 'capital_drawings', 'account_transfers', 'bank_reconciliations',

            // Logs & Misc
            'activity_logs', 'system_logs', 'email_logs', 'sms_logs', 'backup_logs', 'barcode_print_logs', 'due_payment_reminders', 'import_shipments', 'import_shipment_items', 'import_cost_allocations', 'hs_codes', 'report_exports'
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $bar = $this->output->createProgressBar(count($tablesToTruncate));
        $bar->start();

        foreach ($tablesToTruncate as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
            $bar->advance();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $bar->finish();

        $this->newLine(2);
        $this->info('Database cleaned successfully. All operational data has been purged.');
    }
}
