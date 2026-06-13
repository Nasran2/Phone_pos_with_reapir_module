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
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Brands Table
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Units Table
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->timestamps();
        });

        // 4. Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('sku')->unique()->index();
            $table->string('barcode')->unique()->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('retail_price', 15, 2)->default(0.00);
            $table->decimal('wholesale_price', 15, 2)->default(0.00);
            $table->integer('stock_alert_quantity')->default(5);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Product Images Table
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // 6. Product Prices (History) Table
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('cost_price', 15, 2);
            $table->decimal('retail_price', 15, 2);
            $table->decimal('wholesale_price', 15, 2);
            $table->timestamp('created_at')->useCurrent();
        });

        // 7. Product Stock Table
        Schema::create('product_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 8. Product Warranties Table
        Schema::create('product_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('duration_months');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 9. Suppliers Table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('company_name')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('balance', 15, 2)->default(0.00); // Tracks due/balance (+ for credit, - for debit)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        // 10. Supplier Opening Balances Table
        Schema::create('supplier_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 11. Customers Table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->integer('points')->default(0);
            $table->decimal('balance', 15, 2)->default(0.00); // Tracks customer due (+ for receivable, - for prepaid)
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        // 12. Customer Opening Balances Table
        Schema::create('customer_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_opening_balances');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('supplier_opening_balances');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('product_warranties');
        Schema::dropIfExists('product_stock');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
