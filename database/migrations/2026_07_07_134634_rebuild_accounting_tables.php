<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop old tables to avoid conflicts
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('capital_drawings');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');

        // Drop variations that might exist in old database
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('owner_capital_drawings');
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('cash_books');
        Schema::dropIfExists('bank_books');
        Schema::dropIfExists('account_groups');

        Schema::enableForeignKeyConstraints();

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code')->unique();
            $table->string('account_name');
            $table->enum('account_type', ['Asset', 'Liability', 'Equity', 'Income', 'Expense']);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no')->unique();
            $table->date('expense_date');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->foreignId('paid_from_account_id')->constrained('accounts')->onDelete('restrict');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no')->unique();
            $table->date('journal_date');
            $table->string('reference_type')->nullable(); // e.g. Expense, Sale, Purchase
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('capital_drawings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['capital', 'drawing']);
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->constrained('accounts')->comment('Bank/Cash account involved')->onDelete('restrict');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->foreignId('from_account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignId('to_account_id')->constrained('accounts')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('capital_drawings');
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('expense_categories');
        Schema::enableForeignKeyConstraints();
    }
};
