<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'discount_type')) {
                $table->string('discount_type')->default('fixed')->after('total_amount');
            }
            if (!Schema::hasColumn('sales', 'payment_status')) {
                $table->string('payment_status')->default('Due')->after('due_amount')->index();
            }
            if (!Schema::hasColumn('sales', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sales', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('notes');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'product_serial_id')) {
                $table->foreignId('product_serial_id')->nullable()->after('product_id')->constrained('product_serials')->onDelete('set null');
            }
            if (!Schema::hasColumn('sale_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('selling_price');
            }
            if (!Schema::hasColumn('sale_items', 'discount_type')) {
                $table->string('discount_type')->default('fixed')->after('price_type');
            }
            if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('sale_items', 'line_total')) {
                $table->decimal('line_total', 15, 2)->default(0)->after('discount_amount');
            }
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'refund_method')) {
                $table->string('refund_method')->default('No Refund')->after('total_amount');
            }
            if (!Schema::hasColumn('sale_returns', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0)->after('refund_method');
            }
            if (!Schema::hasColumn('sale_returns', 'refund_status')) {
                $table->string('refund_status')->default('Pending')->after('refund_amount')->index();
            }
            if (!Schema::hasColumn('sale_returns', 'reason')) {
                $table->text('reason')->nullable()->after('refund_status');
            }
        });

        Schema::table('sale_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_return_items', 'sale_item_id')) {
                $table->foreignId('sale_item_id')->nullable()->after('sale_return_id')->constrained('sale_items')->onDelete('set null');
            }
            if (!Schema::hasColumn('sale_return_items', 'variant_id')) {
                $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->onDelete('set null');
            }
            if (!Schema::hasColumn('sale_return_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('sale_return_items', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0)->after('unit_price');
            }
        });

        Schema::table('hold_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('hold_sales', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('hold_sales', 'estimated_total')) {
                $table->decimal('estimated_total', 15, 2)->default(0)->after('discount_amount');
            }
        });

        Schema::table('hold_sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('hold_sale_items', 'variant_id')) {
                $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->onDelete('set null');
            }
            if (!Schema::hasColumn('hold_sale_items', 'cost_price')) {
                $table->decimal('cost_price', 15, 2)->default(0)->after('quantity');
            }
        });

        if (!Schema::hasTable('customer_transactions')) {
            Schema::create('customer_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->nullableMorphs('transactionable', 'cust_txn_type_id_index');
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

        DB::table('sales')->orderBy('id')->get()->each(function ($sale) {
            $due = (float) ($sale->due_amount ?? 0);
            $paid = (float) ($sale->paid_amount ?? 0);
            $payable = (float) ($sale->payable_amount ?? 0);
            $status = $sale->status === 'cancelled'
                ? 'Cancelled'
                : ($due <= 0.009 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Due'));

            DB::table('sales')->where('id', $sale->id)->update([
                'payment_status' => $sale->payment_status ?: $status,
                'payable_amount' => $payable ?: (float) ($sale->total_amount ?? 0),
            ]);
        });

        DB::table('sale_items')->where(function ($query) {
            $query->whereNull('unit_price')->orWhere('unit_price', 0);
        })->update([
            'unit_price' => DB::raw('selling_price'),
            'line_total' => DB::raw('subtotal'),
        ]);

        DB::table('hold_sales')->where('estimated_total', 0)->update([
            'estimated_total' => DB::raw('total_amount'),
        ]);

        $this->seedSalesPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }

    private function seedSalesPermissions(): void
    {
        $permissions = [
            'sales.view',
            'sales.invoice_view',
            'sales.print',
            'sales.pdf',
            'sales.whatsapp_share',
            'sales.edit_payment',
            'sales.delete',
            'sales.return',
            'sales_return.view',
            'sales_return.create',
            'sales_return.delete',
            'hold_sales.view',
            'hold_sales.resume',
            'hold_sales.delete',
        ];

        foreach ($permissions as $permission) {
            if (!DB::table('permissions')->where('name', $permission)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Cashier', 'Sales Staff'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                if (!DB::table('role_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->exists()) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }
};
