<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales') || !Schema::hasTable('sale_items') || !Schema::hasTable('products')) {
            return;
        }

        $demoItems = [
            'INV-000121' => [
                ['product_id' => 7, 'quantity' => 1, 'cost_price' => 30000, 'selling_price' => 48500, 'price_type' => 'wholesale'],
            ],
            'INV-000122' => [
                ['product_id' => 1, 'quantity' => 3, 'cost_price' => 500, 'selling_price' => 1200, 'price_type' => 'retail'],
                ['product_id' => 2, 'quantity' => 1, 'cost_price' => 800, 'selling_price' => 1800, 'price_type' => 'retail'],
                ['product_id' => 3, 'quantity' => 2, 'cost_price' => 200, 'selling_price' => 675, 'price_type' => 'retail'],
            ],
            'INV-000123' => [
                ['product_id' => 4, 'quantity' => 1, 'cost_price' => 15000, 'selling_price' => 25800, 'price_type' => 'wholesale'],
            ],
            'INV-000124' => [
                ['product_id' => 2, 'quantity' => 4, 'cost_price' => 800, 'selling_price' => 1800, 'price_type' => 'retail'],
                ['product_id' => 5, 'quantity' => 21, 'cost_price' => 228.57, 'selling_price' => 400, 'price_type' => 'retail'],
            ],
            'INV-000125' => [
                ['product_id' => 1, 'quantity' => 4, 'cost_price' => 500, 'selling_price' => 1200, 'price_type' => 'retail'],
                ['product_id' => 3, 'quantity' => 2, 'cost_price' => 200, 'selling_price' => 600, 'price_type' => 'retail'],
                ['product_id' => 5, 'quantity' => 7.375, 'cost_price' => 216.95, 'selling_price' => 400, 'price_type' => 'retail'],
            ],
        ];

        foreach (DB::table('sales')->orderBy('id')->get() as $sale) {
            $status = $sale->status === 'cancelled'
                ? 'Cancelled'
                : ((float) $sale->due_amount <= 0.009 ? 'Paid' : ((float) $sale->paid_amount > 0 ? 'Partial' : 'Due'));

            DB::table('sales')->where('id', $sale->id)->update(['payment_status' => $status]);

            if (DB::table('sale_items')->where('sale_id', $sale->id)->exists()) {
                continue;
            }

            $items = $demoItems[$sale->invoice_no] ?? null;
            if (!$items) {
                $product = DB::table('products')->orderBy('id')->first();
                if (!$product) {
                    continue;
                }

                $items = [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'cost_price' => (float) $sale->total_cost_amount,
                    'selling_price' => (float) $sale->payable_amount,
                    'price_type' => $sale->pos_type,
                ]];
            }

            foreach ($items as $item) {
                if (!DB::table('products')->where('id', $item['product_id'])->exists()) {
                    continue;
                }

                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['selling_price'];
                $costPrice = (float) $item['cost_price'];
                $lineTotal = round($quantity * $unitPrice, 2);
                $profit = round(($unitPrice - $costPrice) * $quantity, 2);

                DB::table('sale_items')->insert([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => null,
                    'product_serial_id' => null,
                    'quantity' => $quantity,
                    'cost_price' => $costPrice,
                    'selling_price' => $unitPrice,
                    'unit_price' => $unitPrice,
                    'price_type' => $item['price_type'] ?? $sale->pos_type,
                    'discount_type' => 'fixed',
                    'discount_amount' => 0,
                    'line_total' => $lineTotal,
                    'profit_amount' => $profit,
                    'subtotal' => $lineTotal,
                    'created_at' => $sale->created_at ?? now(),
                    'updated_at' => $sale->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['INV-000121', 'INV-000122', 'INV-000123', 'INV-000124', 'INV-000125'] as $invoiceNo) {
            $saleId = DB::table('sales')->where('invoice_no', $invoiceNo)->value('id');
            if ($saleId) {
                DB::table('sale_items')->where('sale_id', $saleId)->delete();
            }
        }
    }
};
