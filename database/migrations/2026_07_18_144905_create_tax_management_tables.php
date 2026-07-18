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
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->string('tax_type', 50)->default('VAT');
            $table->string('period_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date')->nullable();
            
            // Balances
            $table->decimal('opening_payable_balance', 15, 2)->default(0);
            $table->decimal('opening_credit_balance', 15, 2)->default(0);
            
            // Period Totals
            $table->decimal('output_tax', 15, 2)->default(0);
            $table->decimal('input_tax', 15, 2)->default(0);
            $table->decimal('import_tax', 15, 2)->default(0);
            $table->decimal('adjustments', 15, 2)->default(0);
            
            // Final Liabilities & Payments
            $table->decimal('total_liability', 15, 2)->default(0);
            $table->decimal('total_payments', 15, 2)->default(0);
            
            // Closing Balances
            $table->decimal('closing_payable_balance', 15, 2)->default(0);
            $table->decimal('closing_credit_balance', 15, 2)->default(0);
            
            // Status
            $table->string('payment_status', 50)->default('Unpaid'); // Not Due, Unpaid, Partially Paid, Paid, Overpaid, Overdue
            $table->string('period_status', 50)->default('Open'); // Open, Closed, Reopened
            
            // Audit
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 100)->unique();
            $table->string('tax_type', 50)->default('VAT');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method', 50); // Bank Transfer, Online, Cash, Cheque
            
            $table->foreignId('account_id')->nullable(); // Depending on Chart of Accounts setup
            $table->string('bank_reference')->nullable();
            $table->string('ird_reference')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            
            $table->string('status', 50)->default('Approved'); // Draft, Approved, Paid, Reversed
            
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            
            $table->timestamps();
        });

        Schema::create('tax_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_payment_id')->constrained('tax_payments')->cascadeOnDelete();
            $table->foreignId('tax_period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->string('allocation_type', 50)->default('Period VAT'); // Period VAT, Penalties, Overdue Balance
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('tax_period_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->string('balance_type', 50); // Output VAT, Input VAT, Import VAT, Adjustment, Payment
            $table->decimal('amount', 15, 2);
            $table->string('source_type')->nullable(); // e.g. App\Models\Sale
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->date('adjustment_date');
            $table->string('adjustment_type', 50); // Output VAT Increase, Input VAT Decrease, Penalty, etc.
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('attachment_path')->nullable();
            
            $table->string('status', 50)->default('Approved'); // Draft, Approved, Reversed
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_adjustments');
        Schema::dropIfExists('tax_period_balances');
        Schema::dropIfExists('tax_payment_allocations');
        Schema::dropIfExists('tax_payments');
        Schema::dropIfExists('tax_periods');
    }
};
