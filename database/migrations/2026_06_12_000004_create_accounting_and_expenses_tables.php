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
        // 1. Account Groups Table
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
            $table->foreignId('parent_id')->nullable()->constrained('account_groups')->onDelete('set null');
            $table->timestamps();
        });

        // 2. Chart of Accounts Table
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('account_groups')->onDelete('restrict');
            $table->string('code')->unique()->index();
            $table->string('name');
            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Journal Entries Table
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->index();
            $table->string('reference_no')->nullable()->index();
            $table->text('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Journal Entry Items Table
        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 5. Cash Books Table
        Schema::create('cash_books', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 6. Bank Accounts Table
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('bank_name');
            $table->string('account_number')->unique();
            $table->string('branch')->nullable();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 7. Bank Books Table
        Schema::create('bank_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            $table->date('entry_date')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 8. Bank Reconciliations Table
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('book_balance', 15, 2);
            $table->date('reconciled_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 9. Petty Cash Transactions Table
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->decimal('amount', 15, 2);
            $table->enum('transaction_type', ['deposit', 'expense']);
            $table->string('recipient_name')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 10. Ledger Entries Table
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->date('entry_date')->index();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2);
            $table->timestamps();
        });

        // 11. Owner Capital Drawings Table
        Schema::create('owner_capital_drawings', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->enum('transaction_type', ['capital', 'drawing']);
            $table->decimal('amount', 15, 2);
            $table->date('date')->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 12. Expense Categories Table
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 13. Expenses Table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->onDelete('restrict');
            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date')->index();
            $table->string('reference_no')->nullable()->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 14. Expense Documents Table
        Schema::create('expense_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->string('document_path');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_documents');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('owner_capital_drawings');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_books');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('cash_books');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('account_groups');
    }
};
