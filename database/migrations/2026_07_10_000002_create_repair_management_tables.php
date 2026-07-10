<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->string('specialization')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'On Leave'])->default('Active');
            $table->decimal('commission_rate', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repair_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_no')->unique();
            $table->string('barcode')->unique();
            $table->string('qr_code')->nullable();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('store_id')->nullable()->constrained('stores');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('primary_technician_id')->nullable()->constrained('repair_technicians');
            $table->enum('status', [
                'Checked In',
                'Diagnosis',
                'Waiting Approval',
                'Waiting Parts',
                'Assigned',
                'Under Repair',
                'Quality Check',
                'Ready for Pickup',
                'Completed',
                'Delivered',
                'Cancelled',
                'Rejected',
            ])->default('Checked In');
            $table->text('problem_description')->nullable();
            $table->text('repair_notes')->nullable();
            $table->text('customer_request')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('labor_charge', 15, 2)->default(0);
            $table->decimal('parts_total', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('advance_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->boolean('urgent')->default(false);
            $table->boolean('is_warranty')->default(false);
            $table->date('expected_completion_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repair_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->string('device_type')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('imei_1')->nullable();
            $table->string('imei_2')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('color')->nullable();
            $table->string('storage')->nullable();
            $table->string('ram')->nullable();
            $table->string('carrier')->nullable();
            $table->string('pattern_lock')->nullable();
            $table->string('password')->nullable();
            $table->string('pin')->nullable();
            $table->boolean('google_lock')->default(false);
            $table->boolean('apple_id_lock')->default(false);
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->text('accessories_received')->nullable();
            $table->json('physical_condition')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->json('items')->nullable();
            $table->text('custom_items')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->string('type')->nullable();
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('repair_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('repair_technicians');
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('repair_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('store_id')->nullable()->constrained('stores');
            $table->decimal('quantity', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->enum('status', ['Reserved', 'Issued', 'Returned'])->default('Issued');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('repair_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('repair_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('repair_warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->string('warranty_type')->default('Days');
            $table->integer('duration')->default(30);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('type')->default('Internal');
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('repair_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->nullable()->constrained('repair_jobs')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->nullable()->constrained('repair_jobs')->onDelete('cascade');
            $table->string('channel');
            $table->string('event');
            $table->string('recipient')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['Pending', 'Sent', 'Failed'])->default('Pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        $permissions = [
            'repair.view',
            'repair.create',
            'repair.edit',
            'repair.delete',
            'repair.assign',
            'repair.complete',
            'repair.deliver',
            'repair.invoice',
            'repair.parts',
            'repair.report',
            'repair.settings',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_notifications');
        Schema::dropIfExists('repair_activity_logs');
        Schema::dropIfExists('repair_notes');
        Schema::dropIfExists('repair_warranties');
        Schema::dropIfExists('repair_payments');
        Schema::dropIfExists('repair_status_logs');
        Schema::dropIfExists('repair_parts');
        Schema::dropIfExists('repair_assignments');
        Schema::dropIfExists('repair_images');
        Schema::dropIfExists('repair_checklists');
        Schema::dropIfExists('repair_devices');
        Schema::dropIfExists('repair_jobs');
        Schema::dropIfExists('repair_technicians');
    }
};
