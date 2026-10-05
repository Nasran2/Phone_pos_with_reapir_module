<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_statuses', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('name');
            $t->string('color')->default('#2563eb');
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_default')->default(false);
            foreach (['is_completed', 'is_delivered', 'is_cancelled', 'is_ready', 'is_waiting_approval', 'is_waiting_parts'] as $flag) {
                $t->boolean($flag)->default(false);
            }
            $t->timestamps();
        });
        foreach (['repair_problems', 'repair_charge_templates'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->string('name');
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('sort_order')->default(0);
                if ($name === 'repair_charge_templates') {
                    $t->decimal('default_amount', 15, 2)->default(0);
                }
                $t->timestamps();
            });
        }
        Schema::create('repair_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::table('repair_jobs', function (Blueprint $t) {
            $t->foreignId('repair_status_id')->nullable()->constrained('repair_statuses');
            $t->json('problem_snapshot')->nullable();
            $t->decimal('tax_rate', 8, 2)->default(0);
            $t->decimal('cheque_hold_amount', 15, 2)->default(0);
            $t->decimal('ledger_due', 15, 2)->default(0);
            $t->decimal('ledger_hold', 15, 2)->default(0);
            $t->json('ledger_revenue')->nullable();
            $t->string('approval_status')->default('Pending');
            $t->text('approval_note')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->uuid('intake_key')->nullable()->unique();
            $t->index(['store_id', 'repair_status_id', 'expected_completion_date'], 'repair_jobs_workspace_index');
        });
        Schema::table('repair_devices', function (Blueprint $t) {
            $t->string('unlock_type')->default('None');
            $t->text('unlock_secret')->nullable();
        });
        Schema::table('repair_parts', function (Blueprint $t) {
            $t->foreignId('variant_id')->nullable()->constrained('product_variants');
            $t->string('name_snapshot')->nullable();
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('returned_at')->nullable();
            $t->uuid('request_key')->nullable()->unique();
        });
        Schema::table('repair_payments', function (Blueprint $t) {
            $t->foreignId('invoice_payment_id')->nullable()->constrained('invoice_payments');
            $t->foreignId('cheque_id')->nullable()->constrained('cheques');
            $t->string('status')->default('cleared');
            $t->uuid('request_key')->nullable()->unique();
        });
        $statuses = ['Checked In', 'Diagnosis', 'Waiting Approval', 'Waiting Parts', 'Assigned', 'Under Repair', 'Quality Check', 'Ready for Pickup', 'Completed', 'Delivered', 'Cancelled', 'Rejected'];
        foreach ($statuses as $i => $name) {
            $id = DB::table('repair_statuses')->insertGetId(['key' => str_replace(' ', '_', strtolower($name)), 'name' => $name, 'color' => in_array($name, ['Completed', 'Delivered', 'Ready for Pickup']) ? '#059669' : '#2563eb', 'sort_order' => $i,
                'is_default' => $i === 0, 'is_completed' => $name === 'Completed', 'is_delivered' => $name === 'Delivered', 'is_cancelled' => in_array($name, ['Cancelled', 'Rejected']),
                'is_ready' => $name === 'Ready for Pickup', 'is_waiting_approval' => $name === 'Waiting Approval', 'is_waiting_parts' => $name === 'Waiting Parts', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('repair_jobs')->where('status', $name)->update(['repair_status_id' => $id]);
        }
        // Historical issued rows already affected inventory. Mark them so retries never deduct twice.
        DB::table('repair_parts')->whereIn('status', ['Issued', 'Returned'])->update(['issued_at' => DB::raw('created_at')]);
        DB::table('repair_parts')->where('status', 'Returned')->update(['returned_at' => DB::raw('updated_at')]);
        $problems = ['No Power', 'Not Charging', 'Slow Charging', 'Battery Draining', 'Battery Swollen', 'Broken Display', 'Touch Not Working', 'Broken Glass', 'Green Line / Display Line', 'No Network', 'SIM Not Detecting', 'Wi-Fi Problem', 'Bluetooth Problem', 'Speaker Problem', 'Microphone Problem', 'Earpiece Problem', 'Camera Problem', 'Charging Port Problem', 'Power Button Problem', 'Volume Button Problem', 'Fingerprint Problem', 'Face ID Problem', 'Water Damage', 'Heating', 'Restarting', 'Boot Loop', 'Software Problem', 'Forgot Password / Lock', 'Back Glass Broken', 'Motherboard / IC Problem', 'Other'];
        foreach ($problems as $i => $name) {
            DB::table('repair_problems')->insert(['name' => $name, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['Diagnosis', 'Labor', 'Software', 'Service', 'Other'] as $i => $name) {
            DB::table('repair_charge_templates')->insert(['name' => $name, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['repair.payments', 'repair.credentials.view', 'repair.status.change', 'repair.technicians'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $pid = DB::table('permissions')->where('name', $name)->value('id');
            foreach (DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id') as $role) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $role, 'permission_id' => $pid]);
            }
        }
        foreach (['4010' => ['Repair Parts Income', 'Income'], '4011' => ['Repair Service Income', 'Income'], '5010' => ['Repair Parts Cost', 'Expense'], '2210' => ['Repair Tax Payable', 'Liability']] as $code => $values) {
            if (! DB::table('accounts')->where('account_code', $code)->exists()) {
                DB::table('accounts')->insert(['account_code' => $code, 'account_name' => $values[0], 'account_type' => $values[1], 'opening_balance' => 0, 'current_balance' => 0, 'status' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Operational and financial repair history is intentionally retained. Restore a backup to undo this migration.
    }
};
