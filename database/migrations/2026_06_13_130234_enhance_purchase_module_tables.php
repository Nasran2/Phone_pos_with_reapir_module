<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'purchase_no')) {
                $table->string('purchase_no')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('purchases', 'invoice_no')) {
                $table->string('invoice_no')->nullable()->unique()->after('supplier_id');
            }
            if (!Schema::hasColumn('purchases', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('purchase_date');
            }
            if (!Schema::hasColumn('purchases', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('purchases', 'discount_type')) {
                $table->string('discount_type')->default('none')->after('subtotal');
            }
            if (!Schema::hasColumn('purchases', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('purchases', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('purchases', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('purchases', 'shipping_handling')) {
                $table->string('shipping_handling')->default('purchase_total')->after('shipping_cost');
            }
            if (!Schema::hasColumn('purchases', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('shipping_handling');
            }
            if (!Schema::hasColumn('purchases', 'document_path')) {
                $table->string('document_path')->nullable()->after('due_amount');
            }
            if (!Schema::hasColumn('purchases', 'notes')) {
                $table->text('notes')->nullable()->after('document_path');
            }
            if (!Schema::hasColumn('purchases', 'stock_updated')) {
                $table->boolean('stock_updated')->default(false)->after('notes');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('purchase_items', 'cost_price')) {
                $table->decimal('cost_price', 15, 2)->default(0)->after('unit_cost');
            }
            if (!Schema::hasColumn('purchase_items', 'discount_percent')) {
                $table->decimal('discount_percent', 8, 2)->default(0)->after('unit_cost');
            }
            if (!Schema::hasColumn('purchase_items', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent');
            }
            if (!Schema::hasColumn('purchase_items', 'tax_percent')) {
                $table->decimal('tax_percent', 8, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('purchase_items', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_percent');
            }
            if (!Schema::hasColumn('purchase_items', 'line_total')) {
                $table->decimal('line_total', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('purchase_items', 'profit_margin')) {
                $table->decimal('profit_margin', 8, 2)->default(0)->after('line_total');
            }
            if (!Schema::hasColumn('purchase_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('wholesale_price');
            }
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_returns', 'refund_method')) {
                $table->string('refund_method')->default('Supplier Credit')->after('total_amount');
            }
            if (!Schema::hasColumn('purchase_returns', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0)->after('refund_method');
            }
            if (!Schema::hasColumn('purchase_returns', 'refund_status')) {
                $table->string('refund_status')->default('Pending')->after('refund_amount');
            }
            if (!Schema::hasColumn('purchase_returns', 'reason')) {
                $table->text('reason')->nullable()->after('refund_status');
            }
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_return_items', 'purchase_item_id')) {
                $table->foreignId('purchase_item_id')->nullable()->after('purchase_return_id')->constrained('purchase_items')->onDelete('set null');
            }
        });

        if (!Schema::hasTable('supplier_transactions')) {
            Schema::create('supplier_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
                $table->nullableMorphs('transactionable');
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

        DB::table('purchases')->orderBy('id')->get()->each(function ($purchase) {
            $purchaseNo = $purchase->purchase_no ?: ($purchase->invoice_no ?: 'PO-' . str_pad((string) $purchase->id, 5, '0', STR_PAD_LEFT));
            DB::table('purchases')->where('id', $purchase->id)->update([
                'purchase_no' => $purchaseNo,
                'invoice_no' => $purchase->invoice_no ?: $purchaseNo,
                'subtotal' => (float) ($purchase->subtotal ?: ($purchase->total_amount ?: $purchase->grand_total)),
                'grand_total' => (float) ($purchase->grand_total ?: ($purchase->total_amount ?: $purchase->subtotal)),
                'total_amount' => (float) ($purchase->total_amount ?: ($purchase->grand_total ?: $purchase->subtotal)),
            ]);
        });

        $this->seedPurchasePermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_transactions');
    }

    private function seedPurchasePermissions(): void
    {
        $permissions = [
            'purchase.view',
            'purchase.create',
            'purchase.edit',
            'purchase.delete',
            'purchase.receive',
            'purchase.return',
            'purchase.print',
            'purchase.download',
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

        $roleNames = ['Super Admin', 'Admin', 'Stock Manager'];
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }
};
