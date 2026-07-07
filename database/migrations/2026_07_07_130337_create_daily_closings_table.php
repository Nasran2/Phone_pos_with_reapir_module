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
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('retail_sales', 15, 2)->default(0);
            $table->decimal('wholesale_sales', 15, 2)->default(0);
            $table->decimal('customer_due_collections', 15, 2)->default(0);
            $table->decimal('supplier_payments', 15, 2)->default(0);
            $table->decimal('expenses', 15, 2)->default(0);
            $table->decimal('purchase_payments', 15, 2)->default(0);
            $table->decimal('cash_in', 15, 2)->default(0);
            $table->decimal('cash_out', 15, 2)->default(0);
            $table->decimal('expected_cash_balance', 15, 2)->default(0);
            $table->decimal('actual_cash_counted', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->text('closing_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
