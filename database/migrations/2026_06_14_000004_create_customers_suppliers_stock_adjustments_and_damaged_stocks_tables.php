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
        // 1. Enhance Customers Table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0.00);
            }
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('customers', 'status')) {
                $table->boolean('status')->default(true); // true = Active, false = Inactive
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // 2. Enhance Suppliers Table
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0.00);
            }
            if (!Schema::hasColumn('suppliers', 'bank_details')) {
                $table->text('bank_details')->nullable();
            }
            if (!Schema::hasColumn('suppliers', 'status')) {
                $table->boolean('status')->default(true); // true = Active, false = Inactive
            }
            if (!Schema::hasColumn('suppliers', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // 3. Create Customer Transactions Table
        if (!Schema::hasTable('customer_transactions')) {
            Schema::create('customer_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->nullableMorphs('transactionable');
                $table->date('transaction_date')->index();
                $table->string('type')->index();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('reference_no')->nullable()->index();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // 4. Create Supplier Transactions Table
        if (!Schema::hasTable('supplier_transactions')) {
            Schema::create('supplier_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
                $table->nullableMorphs('transactionable');
                $table->date('transaction_date')->index();
                $table->string('type')->index();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('reference_no')->nullable()->index();
                $table->text('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // 5. Create Stock Adjustments Table
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique();
            $table->date('adjustment_date');
            $table->string('adjustment_type'); // increase, decrease, correction, opening_stock
            $table->string('status')->default('approved'); // approved, pending
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 6. Create Stock Adjustment Items Table
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('current_stock', 15, 2);
            $table->decimal('quantity', 15, 2); // negative for decrease, positive for increase
            $table->decimal('new_stock', 15, 2);
            $table->timestamps();
        });

        // 7. Create Damaged Stocks Table
        Schema::dropIfExists('damaged_stocks');

        Schema::create('damaged_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('damage_no')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->string('damage_type'); // water, impact, screen, battery, etc
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, repaired, disposed, returned
            $table->decimal('estimated_loss', 15, 2);
            $table->string('responsible_person')->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 8. Enhance Stock Histories Table
        Schema::table('stock_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_histories', 'quantity_before')) {
                $table->decimal('quantity_before', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('stock_histories', 'reference_type')) {
                $table->string('reference_type')->nullable();
            }
            if (!Schema::hasColumn('stock_histories', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }
            if (!Schema::hasColumn('stock_histories', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn(['quantity_before', 'reference_type', 'reference_id', 'description']);
        });

        Schema::dropIfExists('damaged_stocks');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'bank_details', 'status', 'notes']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'credit_limit', 'status', 'notes']);
        });
    }
};
