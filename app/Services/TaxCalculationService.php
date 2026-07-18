<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * Resolve the final tax mode.
     * Order of precedence: Item Override > Product Override > Global Default
     */
    public function resolveTaxMode(?string $itemOverride, ?string $productMode, string $globalMode): string
    {
        if ($itemOverride && in_array($itemOverride, ['inclusive', 'exclusive'])) {
            return $itemOverride;
        }

        if ($productMode && in_array($productMode, ['inclusive', 'exclusive'])) {
            return $productMode;
        }

        return $globalMode === 'inclusive' ? 'inclusive' : 'exclusive';
    }

    /**
     * Calculate tax details for a single line item.
     * 
     * @param array $params
     * [
     *   'unit_price' => float,
     *   'quantity' => float,
     *   'discount_type' => 'fixed'|'percentage'|null,
     *   'discount_value' => float,
     *   'invoice_discount_amount' => float, // Pro-rated invoice discount for this item
     *   'tax_rate' => float,
     *   'tax_mode' => 'inclusive'|'exclusive',
     *   'tax_status' => 'taxable'|'zero_rated'|'exempt'
     * ]
     * @return array
     */
    public function calculate(array $params): array
    {
        $unitPrice = (float) ($params['unit_price'] ?? 0);
        $quantity = (float) ($params['quantity'] ?? 1);
        $discountType = $params['discount_type'] ?? null;
        $discountValue = (float) ($params['discount_value'] ?? 0);
        $invoiceDiscountAmount = (float) ($params['invoice_discount_amount'] ?? 0);
        
        $taxMode = $params['tax_mode'] ?? 'exclusive';
        $taxStatus = $params['tax_status'] ?? 'taxable';
        $taxRate = (float) ($params['tax_rate'] ?? 0);

        // If not taxable, rate is 0
        if (in_array($taxStatus, ['non_taxable', 'zero_rated', 'exempt'])) {
            $taxRate = 0;
        }

        $grossAmount = $unitPrice * $quantity;

        // Calculate Item Discount
        $itemDiscountAmount = 0;
        if ($discountType === 'percentage' && $discountValue > 0) {
            $itemDiscountAmount = $grossAmount * ($discountValue / 100);
        } elseif ($discountType === 'fixed' && $discountValue > 0) {
            $itemDiscountAmount = $discountValue;
        }
        
        // Ensure discount doesn't exceed gross amount
        if ($itemDiscountAmount > $grossAmount) {
            $itemDiscountAmount = $grossAmount;
        }

        $amountAfterItemDiscount = $grossAmount - $itemDiscountAmount;
        
        // Total discount includes the pro-rated invoice discount
        $totalDiscountAmount = $itemDiscountAmount + $invoiceDiscountAmount;
        if ($totalDiscountAmount > $grossAmount) {
            $totalDiscountAmount = $grossAmount;
        }

        $discountedAmount = $grossAmount - $totalDiscountAmount;

        $taxableAmount = 0;
        $taxAmount = 0;
        $includedTax = 0;
        $addedTax = 0;
        $lineTotal = 0;

        if ($taxMode === 'inclusive') {
            // Formula: Taxable = Inclusive Amount / (1 + Rate)
            // Tax = Inclusive Amount - Taxable
            $taxableAmount = $discountedAmount / (1 + ($taxRate / 100));
            $taxAmount = $discountedAmount - $taxableAmount;
            
            $includedTax = $taxAmount;
            $addedTax = 0;
            $lineTotal = $discountedAmount;
        } else {
            // Exclusive
            // Formula: Taxable = Amount, Tax = Taxable * Rate
            $taxableAmount = $discountedAmount;
            $taxAmount = $taxableAmount * ($taxRate / 100);
            
            $includedTax = 0;
            $addedTax = $taxAmount;
            $lineTotal = $taxableAmount + $taxAmount;
        }

        return [
            'unit_price' => round($unitPrice, 2),
            'quantity' => round($quantity, 2),
            'gross_amount' => round($grossAmount, 2),
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'item_discount_amount' => round($itemDiscountAmount, 2),
            'invoice_discount_amount' => round($invoiceDiscountAmount, 2),
            'total_discount_amount' => round($totalDiscountAmount, 2),
            'amount_after_item_discount' => round($amountAfterItemDiscount, 2),
            'tax_mode' => $taxMode,
            'tax_status' => $taxStatus,
            'tax_rate' => round($taxRate, 4),
            'taxable_amount' => round($taxableAmount, 2),
            'included_tax_amount' => round($includedTax, 2),
            'added_tax_amount' => round($addedTax, 2),
            'tax_amount' => round($taxAmount, 2),
            'line_total' => round($lineTotal, 2)
        ];
    }
}
