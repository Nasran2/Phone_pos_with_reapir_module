<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'tax_mode')) {
                $table->enum('tax_mode', ['use_global', 'inclusive', 'exclusive'])->default('use_global');
            }
            if (!Schema::hasColumn('products', 'tax_status')) {
                $table->enum('tax_status', ['taxable', 'zero_rated', 'exempt'])->default('taxable');
            }
            if (!Schema::hasColumn('products', 'tax_rate_id')) {
                $table->unsignedBigInteger('tax_rate_id')->nullable();
            }
            if (!Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 4)->nullable();
            }
        });

        $headerTables = ['sales', 'purchases', 'sale_returns', 'purchase_returns', 'hold_sales'];
        foreach ($headerTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'taxable_total')) {
                        $table->decimal('taxable_total', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'included_tax_total')) {
                        $table->decimal('included_tax_total', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'added_tax_total')) {
                        $table->decimal('added_tax_total', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'tax_total')) {
                        $table->decimal('tax_total', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'rounding_amount')) {
                        $table->decimal('rounding_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'payable_total') && !Schema::hasColumn($tableName, 'payable_amount') && !Schema::hasColumn($tableName, 'grand_total')) {
                        $table->decimal('payable_total', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'discount_total')) {
                        $table->decimal('discount_total', 15, 2)->default(0);
                    }
                });
            }
        }

        $itemTables = ['sale_items', 'purchase_items', 'sale_return_items', 'purchase_return_items', 'hold_sale_items'];
        foreach ($itemTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'gross_amount')) {
                        $table->decimal('gross_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'discount_type')) {
                        $table->string('discount_type')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'discount_value')) {
                        $table->decimal('discount_value', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'discount_amount')) {
                        $table->decimal('discount_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'amount_after_discount')) {
                        $table->decimal('amount_after_discount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'tax_mode')) {
                        $table->enum('tax_mode', ['inclusive', 'exclusive'])->default('exclusive');
                    }
                    if (!Schema::hasColumn($tableName, 'tax_status')) {
                        $table->enum('tax_status', ['taxable', 'zero_rated', 'exempt'])->default('taxable');
                    }
                    if (!Schema::hasColumn($tableName, 'tax_rate')) {
                        $table->decimal('tax_rate', 8, 4)->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'taxable_amount')) {
                        $table->decimal('taxable_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'included_tax_amount')) {
                        $table->decimal('included_tax_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'added_tax_amount')) {
                        $table->decimal('added_tax_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'tax_amount')) {
                        $table->decimal('tax_amount', 15, 2)->default(0);
                    }
                    if (!Schema::hasColumn($tableName, 'line_total')) {
                        $table->decimal('line_total', 15, 2)->default(0);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['tax_mode', 'tax_status', 'tax_rate_id', 'tax_rate'];
            foreach($columns as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        $headerTables = ['sales', 'purchases', 'sale_returns', 'purchase_returns', 'hold_sales'];
        foreach ($headerTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = ['taxable_total', 'included_tax_total', 'added_tax_total', 'tax_total', 'rounding_amount', 'payable_total', 'discount_total'];
                    foreach($columns as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }

        $itemTables = ['sale_items', 'purchase_items', 'sale_return_items', 'purchase_return_items', 'hold_sale_items'];
        foreach ($itemTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = ['gross_amount', 'discount_value', 'amount_after_discount', 'tax_mode', 'tax_status', 'taxable_amount', 'included_tax_amount', 'added_tax_amount'];
                    foreach($columns as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }
};
