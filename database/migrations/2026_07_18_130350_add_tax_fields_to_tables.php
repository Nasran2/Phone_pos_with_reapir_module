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
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('default_sales_tax_profile_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('default_purchase_tax_profile_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('tax_classification')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sales_tax_profile_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('purchase_tax_profile_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('tax_classification')->nullable();
            $table->string('tax_type')->default('exclusive'); // inclusive or exclusive
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_tax_amount', 12, 2)->default(0)->after('total_amount');
            $table->string('tax_invoice_number')->nullable()->after('invoice_no');
            $table->boolean('is_tax_invoice')->default(false)->after('tax_invoice_number');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 8, 4)->default(0)->after('subtotal');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
            $table->string('tax_classification')->nullable()->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['default_sales_tax_profile_id']);
            $table->dropForeign(['default_purchase_tax_profile_id']);
            $table->dropColumn(['default_sales_tax_profile_id', 'default_purchase_tax_profile_id', 'tax_classification']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sales_tax_profile_id']);
            $table->dropForeign(['purchase_tax_profile_id']);
            $table->dropColumn(['sales_tax_profile_id', 'purchase_tax_profile_id', 'tax_classification', 'tax_type']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_tax_amount', 'tax_invoice_number', 'is_tax_invoice']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'tax_amount', 'tax_classification']);
        });
    }
};
