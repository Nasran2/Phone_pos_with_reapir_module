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
        // 1. Product Variants Table
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('color')->nullable();
            $table->string('storage')->nullable();
            $table->string('ram')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('retail_price', 15, 2)->nullable();
            $table->decimal('wholesale_price', 15, 2)->nullable();
            $table->decimal('stock_quantity', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Stock Histories Table
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->decimal('quantity_change', 15, 2);
            $table->decimal('new_quantity', 15, 2);
            $table->string('type'); // opening_stock, sale, purchase, adjustment, return
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
        Schema::dropIfExists('product_variants');
    }
};
