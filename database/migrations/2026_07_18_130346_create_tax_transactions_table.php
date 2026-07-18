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
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('transaction_type'); // Sale, Purchase, Import
            $table->string('reference_type'); // SaleModel, PurchaseModel, etc.
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_type');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('tax_code');
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('tax_rate', 8, 4);
            $table->decimal('tax_amount', 12, 2);
            $table->enum('input_or_output', ['input', 'output']);
            $table->decimal('recoverable_amount', 12, 2)->default(0);
            $table->decimal('non_recoverable_amount', 12, 2)->default(0);
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
