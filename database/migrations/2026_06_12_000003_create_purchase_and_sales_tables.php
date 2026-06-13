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
        // 1. Purchases Table
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->string('invoice_no')->unique()->index();
            $table->string('reference_no')->nullable()->index();
            $table->date('purchase_date')->index();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('due_amount', 15, 2)->default(0.00);
            $table->string('status')->default('received'); // received, pending, ordered
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Purchase Items Table
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('retail_price', 15, 2);
            $table->decimal('wholesale_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 3. Purchase Payments Table
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Purchase Returns Table
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->string('return_no')->unique()->index();
            $table->date('return_date')->index();
            $table->decimal('total_amount', 15, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 5. Purchase Return Items Table
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 6. Purchase Documents Table
        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->string('document_path');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // 7. Sales Table
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('invoice_no')->unique()->index();
            $table->date('sale_date')->index();
            $table->enum('pos_type', ['retail', 'wholesale'])->default('retail')->index();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('payable_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('due_amount', 15, 2)->default(0.00);
            $table->decimal('total_cost_amount', 15, 2)->default(0.00);
            $table->decimal('total_profit_amount', 15, 2)->default(0.00);
            $table->string('status')->default('final'); // final, draft, quotation
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 8. Sale Items Table
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->enum('price_type', ['retail', 'wholesale'])->default('retail');
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 9. Sale Payments Table
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 10. Sale Returns Table
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('return_no')->unique()->index();
            $table->date('return_date')->index();
            $table->decimal('total_amount', 15, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 11. Sale Return Items Table
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 12. Sale Documents Table
        Schema::create('sale_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->string('document_path');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // 13. Hold Sales Table
        Schema::create('hold_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('reference_no')->unique()->index();
            $table->date('hold_date')->index();
            $table->enum('pos_type', ['retail', 'wholesale'])->default('retail');
            $table->decimal('total_amount', 15, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 14. Hold Sale Items Table
        Schema::create('hold_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hold_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->enum('price_type', ['retail', 'wholesale'])->default('retail');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 15. Supplier General Payments Table
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 16. Supplier Payment Allocations Table
        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        // 17. Customer General Payments Table
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 18. Customer Payment Allocations Table
        Schema::create('customer_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_allocations');
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('hold_sale_items');
        Schema::dropIfExists('hold_sales');
        Schema::dropIfExists('sale_documents');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_documents');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
