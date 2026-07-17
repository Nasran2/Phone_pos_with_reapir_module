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
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('return_type')->default('refund')->after('total_amount')->comment('refund, replacement, exchange');
            $table->foreignId('exchange_sale_id')->nullable()->after('sale_id')->constrained('sales')->onDelete('set null');
        });

        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->string('return_type')->default('refund')->after('sale_return_id')->comment('refund, replacement, exchange');
            $table->foreignId('exchanged_product_id')->nullable()->after('product_id')->constrained('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign(['exchange_sale_id']);
            $table->dropColumn(['return_type', 'exchange_sale_id']);
        });

        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropForeign(['exchanged_product_id']);
            $table->dropColumn(['return_type', 'exchanged_product_id']);
        });
    }
};
