<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payable_type')->index();
            $table->unsignedBigInteger('payable_id')->index();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->decimal('cleared_amount', 15, 2)->default(0);
            $table->decimal('hold_amount', 15, 2)->default(0);
            $table->string('status')->index();
            $table->string('reference_no')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['payable_type', 'payable_id'], 'invoice_payments_payable_index');
        });

        Schema::table('cheques', fn (Blueprint $table) => $table->foreignId('invoice_payment_id')->nullable()->after('id')->constrained('invoice_payments')->nullOnDelete());
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->string('status')->default('cleared')->after('amount');
            $table->foreignId('cheque_id')->nullable()->after('status')->constrained('cheques')->nullOnDelete();
            $table->foreignId('invoice_payment_id')->nullable()->after('cheque_id')->constrained('invoice_payments')->nullOnDelete();
        });

        $permissions = ['payments.mixed','payments.add_multiple','payments.add_multiple_cheques','payments.edit_before_save','payments.allow_overpayment'];
        foreach ($permissions as $name) DB::table('permissions')->updateOrInsert(['name'=>$name], ['guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $roleIds = DB::table('roles')->whereIn('name',['Super Admin','Admin','Administrator','Lorry Manager'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name',$permissions)->pluck('id');
        foreach ($roleIds as $roleId) foreach ($permissionIds as $permissionId) DB::table('role_permissions')->updateOrInsert(['role_id'=>$roleId,'permission_id'=>$permissionId]);
    }

    public function down(): void
    {
        Schema::table('sale_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('invoice_payment_id'));
        Schema::table('sale_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('cheque_id'));
        Schema::table('sale_payments', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('cheques', fn (Blueprint $table) => $table->dropConstrainedForeignId('invoice_payment_id'));
        Schema::dropIfExists('invoice_payments');
    }
};
