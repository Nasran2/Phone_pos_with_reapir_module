<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleInventoryService
{
    public function __construct(private LorryStockService $lorryStock) {}

    /**
     * Deduct inventory once, and only once, after every payment has cleared.
     */
    public function finalizeIfPaid(Sale $sale): bool
    {
        return $this->finalize($sale, false, 'Stock deducted automatically after full payment cleared.');
    }

    /**
     * Deduct inventory before full payment when an authorized operator explicitly
     * releases the order. The stock_deducted_at guard still prevents duplicates.
     */
    public function finalizeNow(Sale $sale, string $reason): bool
    {
        return $this->finalize($sale, true, $reason);
    }

    private function finalize(Sale $sale, bool $force, string $reason): bool
    {
        return DB::transaction(function () use ($sale, $force, $reason) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->stock_deducted_at || $sale->status === 'cancelled') {
                return false;
            }

            if (! $force && ($sale->stock_release_on_payment === false || ! $sale->isPaymentComplete())) {
                return false;
            }

            $sale->load('items');
            if ($sale->items->isEmpty()) {
                throw new RuntimeException("Invoice {$sale->invoice_no} has no items to deduct.");
            }

            foreach ($sale->items as $item) {
                $quantity = (float) $item->quantity;
                if ($quantity <= 0) {
                    throw new RuntimeException("Invoice {$sale->invoice_no} contains an invalid item quantity.");
                }

                if ($item->product_serial_id && abs($quantity - 1.0) > 0.0001) {
                    throw new RuntimeException("A serialized item on invoice {$sale->invoice_no} must have a quantity of one.");
                }

                if ($sale->source_type === 'lorry') {
                    if (! $sale->lorry_id) {
                        throw new RuntimeException("Invoice {$sale->invoice_no} has no lorry stock source.");
                    }

                    $this->lorryStock->issueFromLorry(
                        (int) $sale->lorry_id,
                        (int) $item->product_id,
                        $item->variant_id ? (int) $item->variant_id : null,
                        $quantity,
                        $sale->trip_id ? (int) $sale->trip_id : null,
                        $sale->invoice_no,
                    );
                } else {
                    $this->deductStoreItem($sale, $item, $quantity, $reason);
                }

                if ($item->product_serial_id) {
                    $updated = DB::table('product_serials')
                        ->where('id', $item->product_serial_id)
                        ->where('product_id', $item->product_id)
                        ->where(function ($query) use ($sale) {
                            $query->where('status', 'available')
                                ->orWhere('sale_id', $sale->id);
                        })
                        ->update([
                            'status' => 'sold',
                            'sale_id' => $sale->id,
                            'updated_at' => now(),
                        ]);

                    if (! $updated) {
                        throw new RuntimeException("A serial/IMEI on invoice {$sale->invoice_no} is no longer available.");
                    }
                }
            }

            $sale->forceFill(['stock_deducted_at' => now()])->save();

            return true;
        });
    }

    /** Restore inventory only when this invoice previously deducted it. */
    public function restore(Sale $sale, string $reason = 'Sale deleted', string $historyType = 'sale_deleted'): bool
    {
        return DB::transaction(function () use ($sale, $reason, $historyType) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if (! $sale->stock_deducted_at) {
                return false;
            }

            $sale->load('items');
            foreach ($sale->items as $item) {
                $quantity = (float) $item->quantity;
                if ($sale->source_type === 'lorry' && $sale->lorry_id) {
                    $this->lorryStock->returnToLorry(
                        (int) $sale->lorry_id,
                        (int) $item->product_id,
                        $item->variant_id ? (int) $item->variant_id : null,
                        $quantity,
                        $sale->trip_id ? (int) $sale->trip_id : null,
                        $sale->invoice_no,
                    );
                } else {
                    $this->restoreStoreItem($sale, $item, $quantity, $reason, $historyType);
                }
            }

            DB::table('product_serials')->where('sale_id', $sale->id)->update([
                'status' => 'available',
                'sale_id' => null,
                'updated_at' => now(),
            ]);

            $sale->forceFill(['stock_deducted_at' => null])->save();

            return true;
        });
    }

    private function deductStoreItem(Sale $sale, object $item, float $quantity, string $reason): void
    {
        $storeId = (int) ($sale->store_id ?: Setting::get('default_store_id', 1));

        if ($item->variant_id) {
            $variant = DB::table('product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
            if (! $variant) {
                throw new RuntimeException('The selected product variant no longer exists.');
            }
            $before = (float) $variant->stock_quantity;
            $after = $before - $quantity;
            $this->guardAvailable($after, $sale->invoice_no);
            DB::table('product_variants')->where('id', $item->variant_id)->update(['stock_quantity' => $after, 'updated_at' => now()]);
        } else {
            $stock = DB::table('product_store_stock')
                ->where('product_id', $item->product_id)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();
            $before = (float) ($stock->quantity ?? 0);
            $after = $before - $quantity;
            $this->guardAvailable($after, $sale->invoice_no);
            DB::table('product_store_stock')->updateOrInsert(
                ['product_id' => $item->product_id, 'store_id' => $storeId],
                ['quantity' => $after, 'last_sale' => now(), 'created_at' => now(), 'updated_at' => now()],
            );
        }

        DB::table('stock_histories')->insert([
            'product_id' => $item->product_id,
            'store_id' => $storeId,
            'variant_id' => $item->variant_id,
            'quantity_before' => $before,
            'quantity_change' => -$quantity,
            'new_quantity' => $after,
            'type' => 'sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'reference_no' => $sale->invoice_no,
            'note' => ucfirst($sale->pos_type).' sale - inventory finalized',
            'description' => $reason,
            'created_by' => auth()->id() ?: $sale->created_by,
            'created_at' => now(),
        ]);
    }

    private function restoreStoreItem(Sale $sale, object $item, float $quantity, string $reason, string $historyType): void
    {
        $storeId = (int) ($sale->store_id ?: Setting::get('default_store_id', 1));

        if ($item->variant_id) {
            $variant = DB::table('product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
            $before = (float) ($variant->stock_quantity ?? 0);
            $after = $before + $quantity;
            DB::table('product_variants')->where('id', $item->variant_id)->update(['stock_quantity' => $after, 'updated_at' => now()]);
        } else {
            $stock = DB::table('product_store_stock')->where('product_id', $item->product_id)->where('store_id', $storeId)->lockForUpdate()->first();
            $before = (float) ($stock->quantity ?? 0);
            $after = $before + $quantity;
            DB::table('product_store_stock')->updateOrInsert(
                ['product_id' => $item->product_id, 'store_id' => $storeId],
                ['quantity' => $after, 'created_at' => now(), 'updated_at' => now()],
            );
        }

        DB::table('stock_histories')->insert([
            'product_id' => $item->product_id,
            'store_id' => $storeId,
            'variant_id' => $item->variant_id,
            'quantity_before' => $before,
            'quantity_change' => $quantity,
            'new_quantity' => $after,
            'type' => $historyType,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'reference_no' => $sale->invoice_no,
            'note' => $reason,
            'description' => $reason,
            'created_by' => auth()->id() ?: $sale->created_by,
            'created_at' => now(),
        ]);
    }

    private function guardAvailable(float $quantityAfter, string $invoiceNo): void
    {
        if ($quantityAfter < -0.0001 && ! SystemSettings::bool('allow_negative_stock', false, 'business_settings')) {
            throw new RuntimeException("Insufficient stock to complete payment for invoice {$invoiceNo}. Restock the item and retry.");
        }
    }
}
