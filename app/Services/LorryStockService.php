<?php

namespace App\Services;

use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LorryStockService
{
    public function completeTransfer(int $transferId): object
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = DB::table('lorry_stock_transfers')->where('id', $transferId)->lockForUpdate()->first();

            if (! $transfer) {
                throw new RuntimeException('Transfer not found.');
            }

            if ($transfer->status === 'completed') {
                return $transfer;
            }

            $items = DB::table('lorry_stock_transfer_items')->where('transfer_id', $transferId)->get();
            if ($items->isEmpty()) {
                throw new RuntimeException('Add at least one product before completing this transfer.');
            }

            foreach ($items as $item) {
                $qty = (float) $item->quantity;
                if ($qty <= 0) {
                    throw new RuntimeException('Transfer quantities must be greater than zero.');
                }

                match ($transfer->transfer_type) {
                    'store_to_lorry' => $this->moveStoreToLorry($transfer, $item, $qty),
                    'lorry_to_store' => $this->moveLorryToStore($transfer, $item, $qty),
                    'lorry_to_lorry' => $this->moveLorryToLorry($transfer, $item, $qty),
                    default => throw new RuntimeException('Unsupported transfer type.'),
                };
            }

            DB::table('lorry_stock_transfers')->where('id', $transferId)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('lorry_stock_transfers')->where('id', $transferId)->first();
        });
    }

    public function issueFromLorry(int $lorryId, int $productId, ?int $variantId, float $qty, ?int $tripId = null, ?string $referenceNo = null): float
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($lorryId, $productId, $variantId, $qty, $tripId, $referenceNo) {
            $before = $this->lorryQuantity($lorryId, $productId, $variantId, true);
            $after = $before - $qty;
            $this->guardNegative($after, 'Not enough lorry stock for this product.');
            $this->setLorryQuantity($lorryId, $productId, $variantId, $after, ['last_sold_at' => now()]);
            $this->history($productId, $variantId, null, $lorryId, $tripId, -$qty, $after, 'lorry_sale', $referenceNo, 'Lorry sale stock issue');

            return $after;
        });
    }

    public function returnToLorry(int $lorryId, int $productId, ?int $variantId, float $qty, ?int $tripId = null, ?string $referenceNo = null): float
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($lorryId, $productId, $variantId, $qty, $tripId, $referenceNo) {
            $before = $this->lorryQuantity($lorryId, $productId, $variantId, true);
            $after = $before + $qty;
            $this->setLorryQuantity($lorryId, $productId, $variantId, $after);
            $this->history($productId, $variantId, null, $lorryId, $tripId, $qty, $after, 'lorry_sale_return', $referenceNo, 'Lorry sale return stock');

            return $after;
        });
    }

    public function lorryQuantity(int $lorryId, int $productId, ?int $variantId = null, bool $lock = false): float
    {
        $query = DB::table('lorry_stocks')
            ->where('lorry_id', $lorryId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($variantId) {
                $variantId ? $q->where('variant_id', $variantId) : $q->whereNull('variant_id');
            });

        if ($lock) {
            $query->lockForUpdate();
        }

        return (float) ($query->value('quantity') ?? 0);
    }

    public function storeQuantity(int $storeId, int $productId, ?int $variantId = null, bool $lock = false): float
    {
        if ($variantId) {
            $query = DB::table('product_variants')->where('id', $variantId);
            if ($lock) {
                $query->lockForUpdate();
            }

            return (float) ($query->value('stock_quantity') ?? 0);
        }

        $query = DB::table('product_store_stock')
            ->where('store_id', $storeId)
            ->where('product_id', $productId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return (float) ($query->value('quantity') ?? 0);
    }

    private function moveStoreToLorry(object $transfer, object $item, float $qty): void
    {
        if (! $transfer->from_store_id || ! $transfer->to_lorry_id) {
            throw new RuntimeException('Select a source store and destination lorry.');
        }

        $storeBefore = $this->storeQuantity((int) $transfer->from_store_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $storeAfter = $storeBefore - $qty;
        $this->guardNegative($storeAfter, 'Not enough store stock to load this lorry.');
        $this->setStoreQuantity((int) $transfer->from_store_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $storeAfter);

        $lorryBefore = $this->lorryQuantity((int) $transfer->to_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $lorryAfter = $lorryBefore + $qty;
        $this->setLorryQuantity((int) $transfer->to_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $lorryAfter, ['last_loaded_at' => now()]);

        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, (int) $transfer->from_store_id, null, $transfer->trip_id, -$qty, $storeAfter, 'store_to_lorry', $transfer->transfer_no, 'Loaded stock to lorry');
        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, null, (int) $transfer->to_lorry_id, $transfer->trip_id, $qty, $lorryAfter, 'store_to_lorry', $transfer->transfer_no, 'Received stock from store');
    }

    private function moveLorryToStore(object $transfer, object $item, float $qty): void
    {
        if (! $transfer->from_lorry_id || ! $transfer->to_store_id) {
            throw new RuntimeException('Select a source lorry and destination store.');
        }

        $lorryBefore = $this->lorryQuantity((int) $transfer->from_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $lorryAfter = $lorryBefore - $qty;
        $this->guardNegative($lorryAfter, 'Not enough lorry stock to return this product.');
        $this->setLorryQuantity((int) $transfer->from_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $lorryAfter);

        $storeBefore = $this->storeQuantity((int) $transfer->to_store_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $storeAfter = $storeBefore + $qty;
        $this->setStoreQuantity((int) $transfer->to_store_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $storeAfter);

        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, null, (int) $transfer->from_lorry_id, $transfer->trip_id, -$qty, $lorryAfter, 'lorry_to_store', $transfer->transfer_no, 'Returned stock to store');
        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, (int) $transfer->to_store_id, null, $transfer->trip_id, $qty, $storeAfter, 'lorry_to_store', $transfer->transfer_no, 'Received stock from lorry');
    }

    private function moveLorryToLorry(object $transfer, object $item, float $qty): void
    {
        if (! $transfer->from_lorry_id || ! $transfer->to_lorry_id || $transfer->from_lorry_id === $transfer->to_lorry_id) {
            throw new RuntimeException('Select two different lorries for transfer.');
        }

        $fromBefore = $this->lorryQuantity((int) $transfer->from_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $fromAfter = $fromBefore - $qty;
        $this->guardNegative($fromAfter, 'Not enough source lorry stock for this transfer.');
        $this->setLorryQuantity((int) $transfer->from_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $fromAfter);

        $toBefore = $this->lorryQuantity((int) $transfer->to_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, true);
        $toAfter = $toBefore + $qty;
        $this->setLorryQuantity((int) $transfer->to_lorry_id, (int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, $toAfter, ['last_loaded_at' => now()]);

        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, null, (int) $transfer->from_lorry_id, $transfer->trip_id, -$qty, $fromAfter, 'lorry_to_lorry', $transfer->transfer_no, 'Transferred stock to another lorry');
        $this->history((int) $item->product_id, $item->variant_id ? (int) $item->variant_id : null, null, (int) $transfer->to_lorry_id, $transfer->trip_id, $qty, $toAfter, 'lorry_to_lorry', $transfer->transfer_no, 'Received stock from another lorry');
    }

    private function setStoreQuantity(int $storeId, int $productId, ?int $variantId, float $quantity): void
    {
        if ($variantId) {
            DB::table('product_variants')->where('id', $variantId)->update([
                'stock_quantity' => $quantity,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('product_store_stock')->updateOrInsert(
            ['store_id' => $storeId, 'product_id' => $productId],
            ['quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function setLorryQuantity(int $lorryId, int $productId, ?int $variantId, float $quantity, array $extra = []): void
    {
        $keys = ['lorry_id' => $lorryId, 'product_id' => $productId, 'variant_id' => $variantId];
        $values = array_merge(['quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()], $extra);

        DB::table('lorry_stocks')->updateOrInsert($keys, $values);
    }

    private function guardNegative(float $quantity, string $message): void
    {
        if ($quantity < -0.0001 && SystemSettings::bool('prevent_negative_lorry_stock', true, 'business_settings')) {
            throw new RuntimeException($message);
        }
    }

    private function history(int $productId, ?int $variantId, ?int $storeId, ?int $lorryId, ?int $tripId, float $change, float $newQty, string $type, ?string $referenceNo, string $note): void
    {
        $historyStoreId = $storeId ?: (int) (DB::table('stores')->where('is_default', true)->value('id') ?? 1);

        $payload = [
            'product_id' => $productId,
            'store_id' => $historyStoreId,
            'variant_id' => $variantId,
            'quantity_change' => $change,
            'new_quantity' => $newQty,
            'type' => $type,
            'reference_no' => $referenceNo,
            'note' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'quantity_before' => $newQty - $change,
            'reference_type' => 'distribution',
            'description' => $note,
        ];

        if (Schema::hasColumn('stock_histories', 'location_type')) {
            $payload['location_type'] = $lorryId ? 'lorry' : 'store';
        }
        if (Schema::hasColumn('stock_histories', 'lorry_id')) {
            $payload['lorry_id'] = $lorryId;
        }
        if (Schema::hasColumn('stock_histories', 'trip_id')) {
            $payload['trip_id'] = $tripId;
        }

        DB::table('stock_histories')->insert($payload);
    }
}
