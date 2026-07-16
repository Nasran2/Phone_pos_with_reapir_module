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
        if (! Schema::hasTable('distribution_orders')) {
            return;
        }

        Schema::table('distribution_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('distribution_orders', 'discount_type')) {
                $table->string('discount_type')->default('fixed')->after('subtotal');
            }
            if (! Schema::hasColumn('distribution_orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('reserved_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('distribution_orders')) {
            return;
        }

        Schema::table('distribution_orders', function (Blueprint $table) {
            if (Schema::hasColumn('distribution_orders', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
            if (Schema::hasColumn('distribution_orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
