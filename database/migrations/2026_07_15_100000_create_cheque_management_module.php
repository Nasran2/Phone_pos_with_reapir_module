<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('cheque_reference')->unique();
            $table->enum('cheque_type', ['received', 'issued'])->index();
            $table->string('cheque_number')->index();
            $table->date('cheque_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->string('party_type')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('payee_name')->nullable();
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->unsignedBigInteger('repair_job_id')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('lorry_id')->nullable()->constrained('lorries')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('distribution_trips')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('deposit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('source_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->decimal('hold_amount', 15, 2)->default(0);
            $table->decimal('passed_amount', 15, 2)->default(0);
            $table->decimal('returned_amount', 15, 2)->default(0);
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('passed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('passed_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->date('cleared_date')->nullable();
            $table->string('deposit_reference')->nullable();
            $table->date('return_date')->nullable();
            $table->string('return_reason')->nullable();
            $table->decimal('return_charge', 15, 2)->default(0);
            $table->boolean('auto_processed')->default(false);
            $table->date('auto_process_date')->nullable();
            $table->string('previous_status')->nullable();
            $table->foreignId('previous_cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->foreignId('replacement_cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['cheque_number', 'bank_name', 'account_number'], 'cheques_identity_unique');
        });

        Schema::create('cheque_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained('cheques')->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('note')->nullable();
            $table->string('action_type');
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_system_action')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('cheque_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained('cheques')->cascadeOnDelete();
            $table->date('reminder_date');
            $table->string('reminder_type');
            $table->string('channel')->default('in_app');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['cheque_id', 'reminder_date', 'reminder_type', 'channel'], 'cheque_reminder_unique');
        });

        foreach (['sales', 'purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'cheque_hold_amount')) {
                    $table->decimal('cheque_hold_amount', 15, 2)->default(0)->after('paid_amount');
                }
            });
        }
        foreach (['customers', 'suppliers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'cheque_hold_balance')) {
                    $table->decimal('cheque_hold_balance', 15, 2)->default(0)->after('balance');
                }
            });
        }

        $permissions = [
            'cheques.view','cheques.dashboard','cheques.received.view','cheques.received.create','cheques.received.edit',
            'cheques.issued.view','cheques.issued.create','cheques.issued.edit','cheques.mark_passed','cheques.mark_returned',
            'cheques.cancel','cheques.replace','cheques.calendar','cheques.reports','cheques.export_pdf','cheques.export_excel',
            'cheques.settings','cheques.auto_process','cheques.view_bank_details',
        ];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        }
        $adminRoleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        foreach ($adminRoleIds as $roleId) foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        $lorryRoleId = DB::table('roles')->where('name', 'Lorry Manager')->value('id');
        if ($lorryRoleId) foreach (DB::table('permissions')->whereIn('name', ['cheques.view','cheques.dashboard','cheques.received.view','cheques.received.create','cheques.mark_passed','cheques.mark_returned'])->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id'=>$lorryRoleId,'permission_id'=>$permissionId]);
        }

        $accounts = [
            ['account_code' => '1105', 'account_name' => 'Customer Receivable', 'account_type' => 'Asset'],
            ['account_code' => '1110', 'account_name' => 'Cheques Receivable', 'account_type' => 'Asset'],
            ['account_code' => '1111', 'account_name' => 'Returned Cheques Receivable', 'account_type' => 'Asset'],
            ['account_code' => '2105', 'account_name' => 'Supplier Payable', 'account_type' => 'Liability'],
            ['account_code' => '2110', 'account_name' => 'Cheques Payable', 'account_type' => 'Liability'],
            ['account_code' => '2111', 'account_name' => 'Returned Cheques Payable', 'account_type' => 'Liability'],
        ];
        foreach ($accounts as $account) DB::table('accounts')->updateOrInsert(['account_code' => $account['account_code']], $account + ['opening_balance' => 0, 'current_balance' => 0, 'status' => true, 'created_at' => now(), 'updated_at' => now()]);

        $settings = [
            'cheque_enabled'=>'1','cheque_received_enabled'=>'1','cheque_issued_enabled'=>'1','cheque_require_date'=>'1',
            'cheque_require_bank'=>'1','cheque_require_attachment'=>'0','cheque_allow_backdated'=>'1','cheque_allow_future'=>'1',
            'cheque_allow_duplicate_different_account'=>'1','cheque_allow_overpayment'=>'0','cheque_reminders_enabled'=>'1',
            'cheque_reminder_days'=>'3','cheque_repeat_reminders'=>'1','cheque_dashboard_reminders'=>'1','cheque_auto_enabled'=>'1',
            'cheque_auto_grace_days'=>'7','cheque_auto_action'=>'passed','cheque_auto_apply_to'=>'both','cheque_auto_manager_approval'=>'0',
            'cheque_auto_warning_days'=>'1','cheque_require_return_reason'=>'1','cheque_default_return_charge'=>'0',
            'cheque_add_return_charge_to_due'=>'1','cheque_allow_replacement'=>'1','cheque_received_prefix'=>'RCHQ',
            'cheque_issued_prefix'=>'ICHQ','cheque_return_prefix'=>'RET',
        ];
        foreach ($settings as $key => $value) DB::table('business_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_reminders');
        Schema::dropIfExists('cheque_status_logs');
        Schema::dropIfExists('cheques');
        foreach (['sales', 'purchases'] as $tableName) if (Schema::hasColumn($tableName, 'cheque_hold_amount')) Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('cheque_hold_amount'));
        foreach (['customers', 'suppliers'] as $tableName) if (Schema::hasColumn($tableName, 'cheque_hold_balance')) Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('cheque_hold_balance'));
    }
};
