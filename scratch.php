<?php
    private function editSalePayload(int $saleId): ?array
    {
        $sale = \App\Models\Sale::with(['customer', 'items.product', 'items.variant', 'items.productSerial'])->find($saleId);

        if (! $sale) {
            return null;
        }

        $items = $sale->items->map(function ($item) {
            $variantLabel = $item->variant ? collect([$item->variant->color, $item->variant->storage, $item->variant->ram])->filter()->implode(' / ') : null;

            return [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'variant_label' => $variantLabel,
                'name' => $item->product->name,
                'quantity' => (float) $item->quantity,
                'cost_price' => (float) $item->cost_price,
                'selling_price' => (float) $item->selling_price,
                'discount_amount' => (float) $item->discount_amount,
                'discount_type' => $item->discount_type ?? 'percentage',
                'line_total' => (float) $item->subtotal,
                'tax_mode' => $item->tax_mode,
                'tax_status' => $item->tax_status,
                'tax_rate' => (float) ($item->tax_rate ?? 0),
                'note' => '',
                'stock' => 999, // We don't strictly need stock validation when editing because we'll just restore anyway, but keeping a fallback
                'serial_no' => $item->productSerial ? $item->productSerial->serial_number : null,
            ];
        })->values();

        return [
            'id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'customer_name' => $sale->customer ? $sale->customer->name : null,
            'discountAmount' => (float) $sale->discount_amount,
            'discountType' => $sale->discount_type ?? 'percentage',
            'taxPercentage' => (float) SystemSettings::number('default_sale_tax', 15, 'tax_settings'),
            'shippingCost' => 0,
            'items' => $items,
        ];
    }
