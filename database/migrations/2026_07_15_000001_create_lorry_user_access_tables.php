<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type')->default('office')->after('is_active')->index();
            $table->foreignId('default_lorry_id')->nullable()->after('user_type')->constrained('lorries')->nullOnDelete();
        });

        Schema::create('lorry_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lorry_id')->constrained('lorries')->cascadeOnDelete();
            $table->string('lorry_role')->index();
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_sell')->default(false);
            $table->boolean('can_create_orders')->default(false);
            $table->boolean('can_collect')->default(false);
            $table->boolean('can_add_expenses')->default(false);
            $table->boolean('can_view_cost')->default(false);
            $table->boolean('can_view_profit')->default(false);
            $table->boolean('can_reconcile')->default(false);
            $table->date('active_from');
            $table->date('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'lorry_id']);
        });

        Schema::create('distribution_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('lorry_id')->constrained('lorries')->restrictOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('order_date')->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('discount_type')->default('fixed');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('reserved_stock')->default(false);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('distribution_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('distribution_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('reserved_quantity', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        $permissions = ['my_lorry.dashboard','my_lorry.stock','my_lorry.orders.view','my_lorry.orders.create','my_lorry.orders.edit','my_lorry.orders.cancel','my_lorry.sales.view','my_lorry.sales.create','my_lorry.sales.return','my_lorry.customers.view','my_lorry.customers.create','my_lorry.collections.view','my_lorry.collections.create','my_lorry.expenses.view','my_lorry.expenses.create','my_lorry.trips.view','my_lorry.trips.update','my_lorry.reconciliation.view','my_lorry.reconciliation.submit','my_lorry.reports.view','my_lorry.reports.export_pdf','my_lorry.reports.export_excel','lorry.view_cost','lorry.view_gross_profit','lorry.view_net_profit','lorry.view_stock_value'];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_order_items');
        Schema::dropIfExists('distribution_orders');
        Schema::dropIfExists('lorry_user_assignments');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_lorry_id');
            $table->dropColumn('user_type');
        });
    }
};
