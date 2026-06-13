<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchases') || !Schema::hasTable('purchase_items') || !Schema::hasTable('products')) {
            return;
        }

        $demoItems = [
            'PO-000081' => [
                ['product_id' => 6, 'quantity' => 0.25, 'cost_price' => 180000, 'retail_price' => 245000, 'wholesale_price' => 224000],
                ['product_id' => 4, 'quantity' => 1, 'cost_price' => 12000, 'retail_price' => 24500, 'wholesale_price' => 21000],
                ['product_id' => 2, 'quantity' => 5, 'cost_price' => 1684, 'retail_price' => 1800, 'wholesale_price' => 1400],
            ],
            'PO-000082' => [
                ['product_id' => 1, 'quantity' => 10, 'cost_price' => 500, 'retail_price' => 1200, 'wholesale_price' => 900],
                ['product_id' => 2, 'quantity' => 5, 'cost_price' => 800, 'retail_price' => 1800, 'wholesale_price' => 1400],
                ['product_id' => 4, 'quantity' => 0.8291667, 'cost_price' => 12000, 'retail_price' => 24500, 'wholesale_price' => 21000],
            ],
            'PO-000083' => [
                ['product_id' => 4, 'quantity' => 2, 'cost_price' => 12000, 'retail_price' => 24500, 'wholesale_price' => 21000],
                ['product_id' => 2, 'quantity' => 10, 'cost_price' => 800, 'retail_price' => 1800, 'wholesale_price' => 1400],
                ['product_id' => 3, 'quantity' => 24.25, 'cost_price' => 200, 'retail_price' => 600, 'wholesale_price' => 450],
            ],
            'PO-000084' => [
                ['product_id' => 6, 'quantity' => 0.2, 'cost_price' => 180000, 'retail_price' => 245000, 'wholesale_price' => 224000],
                ['product_id' => 1, 'quantity' => 10, 'cost_price' => 500, 'retail_price' => 1200, 'wholesale_price' => 900],
                ['product_id' => 5, 'quantity' => 42.3, 'cost_price' => 100, 'retail_price' => 400, 'wholesale_price' => 250],
            ],
            'PO-000085' => [
                ['product_id' => 7, 'quantity' => 0.4, 'cost_price' => 150000, 'retail_price' => 210000, 'wholesale_price' => 189000],
                ['product_id' => 4, 'quantity' => 1, 'cost_price' => 12000, 'retail_price' => 24500, 'wholesale_price' => 21000],
                ['product_id' => 2, 'quantity' => 13.25, 'cost_price' => 800, 'retail_price' => 1800, 'wholesale_price' => 1400],
            ],
        ];

        foreach (DB::table('purchases')->orderBy('id')->get() as $purchase) {
            if (DB::table('purchase_items')->where('purchase_id', $purchase->id)->exists()) {
                continue;
            }

            $items = $demoItems[$purchase->invoice_no] ?? $demoItems[$purchase->purchase_no ?? ''] ?? null;
            if (!$items) {
                $product = DB::table('products')->orderBy('id')->first();
                if (!$product) {
                    continue;
                }

                $items = [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'cost_price' => (float) ($purchase->grand_total ?: $purchase->total_amount),
                    'retail_price' => (float) $product->retail_price,
                    'wholesale_price' => (float) $product->wholesale_price,
                ]];
            }

            foreach ($items as $item) {
                if (!DB::table('products')->where('id', $item['product_id'])->exists()) {
                    continue;
                }

                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['cost_price'];
                $lineTotal = round($quantity * $unitCost, 2);

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'cost_price' => $unitCost,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                    'profit_margin' => 0,
                    'retail_price' => $item['retail_price'],
                    'wholesale_price' => $item['wholesale_price'],
                    'subtotal' => $lineTotal,
                    'created_at' => $purchase->created_at ?? now(),
                    'updated_at' => $purchase->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['PO-000081', 'PO-000082', 'PO-000083', 'PO-000084', 'PO-000085'] as $purchaseNo) {
            $purchaseId = DB::table('purchases')
                ->where('invoice_no', $purchaseNo)
                ->orWhere('purchase_no', $purchaseNo)
                ->value('id');

            if ($purchaseId) {
                DB::table('purchase_items')->where('purchase_id', $purchaseId)->delete();
            }
        }
    }
};
