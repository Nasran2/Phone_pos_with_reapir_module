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
        // 1. Stock Adjustments Table
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique()->index();
            $table->date('adjustment_date')->index();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Stock Adjustment Items Table
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->enum('adjustment_type', ['increase', 'decrease']);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // 3. Damaged Stocks Table
        Schema::create('damaged_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->decimal('loss_amount', 15, 2);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Stock Movements Table
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2); // Can be + or -
            $table->enum('type', ['purchase', 'sale', 'purchase_return', 'sale_return', 'stock_adjustment', 'damaged_stock', 'stock_transfer']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 5. Branches Table
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Branch Users Pivot Table
        Schema::create('branch_users', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->primary(['branch_id', 'user_id']);
        });

        // 7. Store Locations Table
        Schema::create('store_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        // 8. Product Location Stock Table
        Schema::create('product_location_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_location_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 9. Report Exports Table
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name');
            $table->string('format'); // PDF, Excel, CSV
            $table->string('file_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 10. Daily Summaries Table
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->index();
            $table->decimal('total_sales', 15, 2)->default(0.00);
            $table->decimal('retail_sales', 15, 2)->default(0.00);
            $table->decimal('wholesale_sales', 15, 2)->default(0.00);
            $table->decimal('total_purchases', 15, 2)->default(0.00);
            $table->decimal('total_profit', 15, 2)->default(0.00);
            $table->decimal('total_expenses', 15, 2)->default(0.00);
            $table->decimal('customer_due_collected', 15, 2)->default(0.00);
            $table->decimal('supplier_due_paid', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 11. Product Daily Summaries Table
        Schema::create('product_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_sold', 15, 2)->default(0.00);
            $table->decimal('total_sales_amount', 15, 2)->default(0.00);
            $table->decimal('total_cost_amount', 15, 2)->default(0.00);
            $table->decimal('total_profit_amount', 15, 2)->default(0.00);
            $table->timestamps();
            $table->unique(['date', 'product_id']);
        });

        // 12. Customer Due Summaries Table
        Schema::create('customer_due_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('due_amount', 15, 2);
            $table->date('date')->index();
            $table->timestamps();
        });

        // 13. Supplier Due Summaries Table
        Schema::create('supplier_due_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->decimal('due_amount', 15, 2);
            $table->date('date')->index();
            $table->timestamps();
        });

        // 14. Low Stock Alerts Table
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('current_stock', 15, 2);
            $table->decimal('alert_quantity', 15, 2);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 15. Due Payment Reminders Table
        Schema::create('due_payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->enum('party_type', ['Customer', 'Supplier']);
            $table->unsignedBigInteger('party_id'); // Can map to customer_id or supplier_id
            $table->decimal('due_amount', 15, 2);
            $table->date('due_date')->index();
            $table->string('status')->default('pending'); // pending, sent, ignored
            $table->timestamps();
        });

        // 16. SMS Logs Table
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->index();
            $table->text('message');
            $table->string('status')->default('sent');
            $table->timestamp('sent_at')->useCurrent();
        });

        // 17. Email Logs Table
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('subject');
            $table->string('status')->default('sent');
            $table->timestamp('sent_at')->useCurrent();
        });

        // 18. Backup Logs Table
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_size');
            $table->string('status');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 19. System Logs Table
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_level');
            $table->text('message');
            $table->text('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('due_payment_reminders');
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('supplier_due_summaries');
        Schema::dropIfExists('customer_due_summaries');
        Schema::dropIfExists('product_daily_summaries');
        Schema::dropIfExists('daily_summaries');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('product_location_stock');
        Schema::dropIfExists('store_locations');
        Schema::dropIfExists('branch_users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('damaged_stocks');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
