<?php

namespace App\Services\Repairs;

use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\ProductVariant;
use App\Models\RepairJob;
use App\Models\RepairPart;
use App\Models\StockHistory;
use App\Support\RepairMoney as Money;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairInventoryService
{
    /** @param array<string, mixed> $data */
    public function add(RepairJob $job, array $data): RepairPart
    {
        return DB::transaction(function () use ($job, $data) {
            $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            $this->editable($job);
            if ($existing = RepairPart::where('request_key', $data['request_key'])->first()) {
                abort_unless($existing->repair_job_id === $job->id, 409);

                return $existing;
            }
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $variant = null;
            if ($product->variants()->exists()) {
                if (empty($data['variant_id'])) {
                    throw ValidationException::withMessages(['variant_id' => 'Choose the exact product variant.']);
                }
                $variant = ProductVariant::whereKey($data['variant_id'])->where('product_id', $product->id)->where('is_active', true)->lockForUpdate()->firstOrFail();
            } elseif (! empty($data['variant_id'])) {
                throw ValidationException::withMessages(['variant_id' => 'This product does not have that variant.']);
            }
            $qty = Money::cents($data['quantity']);
            $price = Money::cents($data['selling_price'] ?? ($variant ? $variant->retail_price : $product->retail_price));
            $part = $job->parts()->create(['product_id' => $product->id, 'variant_id' => $variant?->id, 'store_id' => $job->store_id, 'name_snapshot' => $product->name.($variant ? ' / '.implode(' ', array_filter([$variant->color, $variant->storage, $variant->ram])) : ''), 'quantity' => Money::decimal($qty), 'cost_price' => $variant ? $variant->cost_price : $product->cost_price, 'selling_price' => Money::decimal($price), 'line_total' => Money::decimal(Money::multiplyDivide($qty, $price, 100, true)), 'status' => 'Reserved', 'request_key' => $data['request_key']]);
            if (($data['status'] ?? 'Issued') === 'Issued') {
                $this->changeLocked($job, $part, 'issue');
            }
            app(RepairPricingService::class)->refresh($job);
            app(RepairActivityService::class)->log($job, 'Part added', $part->name_snapshot.' ('.$part->status.')');

            return $part;
        }, 3);
    }

    public function change(RepairJob $job, RepairPart $part, string $action): void
    {
        DB::transaction(function () use ($job, $part, $action) {
            $job = RepairJob::whereKey($job->id)->lockForUpdate()->firstOrFail();
            $this->editable($job);
            $part = $job->parts()->whereKey($part->id)->lockForUpdate()->firstOrFail();
            $this->changeLocked($job, $part, $action);
            app(RepairPricingService::class)->refresh($job);
        }, 3);
    }

    public function changeLocked(RepairJob $job, RepairPart $part, string $action): void
    {
        if ($action === 'issue' && $part->issued_at) {
            return;
        }
        if ($action === 'return' && $part->returned_at) {
            return;
        }
        if ($action === 'issue' && $part->status !== 'Reserved') {
            throw ValidationException::withMessages(['part' => 'Only reserved parts can be issued.']);
        }
        if ($action === 'return' && ! $part->issued_at) {
            $part->update(['status' => 'Returned', 'returned_at' => now()]);
            app(RepairActivityService::class)->log($job, 'Part reservation released', $part->name_snapshot);

            return;
        }
        $qty = Money::cents($part->quantity);
        $change = $action === 'issue' ? -$qty : $qty;
        if ($part->variant_id) {
            $stock = ProductVariant::whereKey($part->variant_id)->lockForUpdate()->firstOrFail();
        } else {
            if (! $part->store_id) {
                throw ValidationException::withMessages(['store_id' => 'Select a store before issuing parts.']);
            }
            ProductStoreStock::firstOrCreate(['product_id' => $part->product_id, 'store_id' => $part->store_id], ['quantity' => 0]);
            $stock = ProductStoreStock::where(['product_id' => $part->product_id, 'store_id' => $part->store_id])->lockForUpdate()->firstOrFail();
        }
        $field = $part->variant_id ? 'stock_quantity' : 'quantity';
        $before = Money::cents($stock->getAttribute($field));
        $after = $before + $change;
        if ($after < 0 && ! SystemSettings::bool('allow_negative_stock', false, 'business_settings')) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock at the selected store or variant.']);
        }
        $stock->setAttribute($field, Money::decimal($after));
        $stock->save();
        StockHistory::create(['product_id' => $part->product_id, 'store_id' => $part->store_id, 'variant_id' => $part->variant_id, 'quantity_before' => Money::decimal($before), 'quantity_change' => Money::decimal($change), 'new_quantity' => Money::decimal($after), 'type' => $action === 'issue' ? 'repair_part_issued' : 'repair_part_returned', 'reference_type' => RepairJob::class, 'reference_id' => $job->id, 'reference_no' => $job->job_no, 'note' => 'Device service '.$job->job_no, 'description' => 'Device service part '.$action, 'created_by' => auth()->id(), 'created_at' => now()]);
        $cost = Money::multiplyDivide($qty, Money::cents($part->cost_price), 100, true) * ($action === 'issue' ? 1 : -1);
        app(RepairAccountingService::class)->post($job, 'repair_stock', ['5010' => $cost, '1004' => -$cost]);
        $part->forceFill($action === 'issue' ? ['status' => 'Issued', 'issued_at' => now(), 'issued_by' => auth()->id()] : ['status' => 'Returned', 'returned_at' => now()])->save();
        app(RepairActivityService::class)->log($job, $action === 'issue' ? 'Part issued' : 'Part returned', $part->name_snapshot);
    }

    public function editable(RepairJob $job): void
    {
        if ($job->finalized) {
            throw ValidationException::withMessages(['job' => 'This job is finalized. Its device details, stock and charges are preserved.']);
        }
    }
}
