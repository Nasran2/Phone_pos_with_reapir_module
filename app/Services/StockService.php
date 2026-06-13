<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockHistory;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Safely adjust a product's stock and record history.
     */
    public function adjustStock(
        Product $product,
        float $quantityChange,
        string $type, // 'adjustment', 'damaged', 'returned_to_stock', 'sale', 'purchase', etc.
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $referenceNo = null,
        ?string $note = null,
        ?int $userId = null
    ): ProductStock {
        return DB::transaction(function () use (
            $product,
            $quantityChange,
            $type,
            $referenceType,
            $referenceId,
            $description,
            $referenceNo,
            $note,
            $userId
        ) {
            $stock = $product->stock;
            if (!$stock) {
                $stock = ProductStock::create([
                    'product_id' => $product->id,
                    'quantity' => 0.00,
                ]);
            }

            $quantityBefore = $stock->quantity;
            $newQuantity = $quantityBefore + $quantityChange;
            $stock->quantity = $newQuantity;
            $stock->save();

            // Log stock history
            StockHistory::create([
                'product_id' => $product->id,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'new_quantity' => $newQuantity,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'note' => $note,
                'description' => $description ?: "Stock adjusted by {$quantityChange} units.",
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Add activity log
            ActivityLog::log(
                'Stock Adjustment',
                'Stock',
                "Product: {$product->name} (SKU: {$product->sku}) stock changed by {$quantityChange}. New Stock: {$newQuantity}.",
                $userId
            );

            return $stock;
        });
    }
}
