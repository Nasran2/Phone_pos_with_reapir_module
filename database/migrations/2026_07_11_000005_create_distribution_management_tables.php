<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lorries', function (Blueprint $table) {
            $table->id();
            $table->string('lorry_no')->unique();
            $table->string('plate_no')->nullable()->index();
            $table->string('name');
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('helper_name')->nullable();
            $table->string('route_area')->nullable();
            $table->string('capacity_note')->nullable();
            $table->decimal('opening_odometer', 12, 2)->default(0);
            $table->decimal('current_odometer', 12, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('lorry_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lorry_id')->constrained('lorries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->decimal('maximum_stock', 15, 2)->nullable();
            $table->timestamp('last_loaded_at')->nullable();
            $table->timestamp('last_sold_at')->nullable();
            $table->timestamps();
            $table->unique(['lorry_id', 'product_id', 'variant_id'], 'lorry_stock_unique_item');
        });

        Schema::create('distribution_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_no')->unique();
            $table->foreignId('lorry_id')->constrained('lorries')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('helper_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('opening_odometer', 12, 2)->default(0);
            $table->decimal('closing_odometer', 12, 2)->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->string('status')->default('planned')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lorry_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->string('transfer_type')->index();
            $table->foreignId('from_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('to_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('from_lorry_id')->nullable()->constrained('lorries')->nullOnDelete();
            $table->foreignId('to_lorry_id')->nullable()->constrained('lorries')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->date('transfer_date')->index();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lorry_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('lorry_stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lorry_collections', function (Blueprint $table) {
            $table->id();
            $table->string('collection_no')->unique();
            $table->foreignId('lorry_id')->constrained('lorries')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->date('collection_date')->index();
            $table->string('payment_method')->default('Cash');
            $table->decimal('amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lorry_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_no')->unique();
            $table->foreignId('lorry_id')->constrained('lorries')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->date('reconciliation_date')->index();
            $table->decimal('sales_total', 15, 2)->default(0);
            $table->decimal('collections_total', 15, 2)->default(0);
            $table->decimal('expenses_total', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->default(0);
            $table->decimal('counted_cash', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lorry_sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('lorry_id')->nullable()->constrained('lorries')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->date('return_date')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'source_type')) {
                $table->string('source_type')->default('store')->after('store_id')->index();
            }
            if (! Schema::hasColumn('sales', 'lorry_id')) {
                $table->foreignId('lorry_id')->nullable()->after('source_type')->constrained('lorries')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('lorry_id')->constrained('distribution_trips')->nullOnDelete();
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'lorry_id')) {
                $table->foreignId('lorry_id')->nullable()->after('paid_from_account_id')->constrained('lorries')->nullOnDelete();
            }
            if (! Schema::hasColumn('expenses', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('lorry_id')->constrained('distribution_trips')->nullOnDelete();
            }
        });

        Schema::table('stock_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_histories', 'location_type')) {
                $table->string('location_type')->default('store')->after('store_id')->index();
            }
            if (! Schema::hasColumn('stock_histories', 'lorry_id')) {
                $table->foreignId('lorry_id')->nullable()->after('location_type')->constrained('lorries')->nullOnDelete();
            }
            if (! Schema::hasColumn('stock_histories', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('lorry_id')->constrained('distribution_trips')->nullOnDelete();
            }
        });

        $now = now();
        $settings = [
            'enable_distribution_module' => '1',
            'allow_lorry_sales_without_trip' => '1',
            'require_lorry_reconciliation' => '0',
            'prevent_negative_lorry_stock' => '1',
            'default_lorry_sale_payment_method' => 'Cash',
        ];

        foreach ($settings as $key => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissions = [
            'distribution.view',
            'distribution.dashboard',
            'distribution.settings',
            'lorries.view',
            'lorries.create',
            'lorries.edit',
            'lorries.delete',
            'lorry_stock.view',
            'lorry_stock.load',
            'lorry_stock.return',
            'lorry_stock.transfer',
            'distribution_trips.view',
            'distribution_trips.create',
            'distribution_trips.edit',
            'lorry_sales.view',
            'lorry_sales.create',
            'lorry_returns.view',
            'lorry_returns.create',
            'lorry_expenses.view',
            'lorry_expenses.create',
            'lorry_collections.view',
            'lorry_collections.create',
            'lorry_reconciliation.view',
            'lorry_reconciliation.create',
            'distribution_reports.view',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $adminRoleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id');
        foreach ($adminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        if (DB::table('lorries')->count() === 0) {
            DB::table('lorries')->insert([
                [
                    'lorry_no' => 'LRY-001',
                    'plate_no' => 'WP DEMO 1001',
                    'name' => 'Colombo Route Lorry',
                    'driver_name' => 'Nimal Perera',
                    'driver_phone' => '0777001001',
                    'route_area' => 'Colombo Metro',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'lorry_no' => 'LRY-002',
                    'plate_no' => 'WP DEMO 1002',
                    'name' => 'Gampaha Route Lorry',
                    'driver_name' => 'Kasun Silva',
                    'driver_phone' => '0777001002',
                    'route_area' => 'Gampaha',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            foreach (['trip_id', 'lorry_id', 'location_type'] as $column) {
                if (Schema::hasColumn('stock_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            foreach (['trip_id', 'lorry_id'] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            foreach (['trip_id', 'lorry_id', 'source_type'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('lorry_sale_returns');
        Schema::dropIfExists('lorry_reconciliations');
        Schema::dropIfExists('lorry_collections');
        Schema::dropIfExists('lorry_stock_transfer_items');
        Schema::dropIfExists('lorry_stock_transfers');
        Schema::dropIfExists('distribution_trips');
        Schema::dropIfExists('lorry_stocks');
        Schema::dropIfExists('lorries');
    }
};
