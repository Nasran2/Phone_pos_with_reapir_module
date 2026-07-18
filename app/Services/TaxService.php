<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TaxRate;
use App\Models\TaxTransaction;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Cache;

class TaxService
{
    /**
     * Get the applicable tax rate for a specific product and transaction type.
     * Returns null if no tax applies.
     */
    public function getApplicableTaxRate(Product $product, string $type = 'sales')
    {
        if (!SystemSettings::bool('enable_tax_system', true, 'tax_settings')) {
            return null;
        }

        // Product specific override
        if ($type === 'sales' && $product->default_sales_tax_profile_id) {
            return $this->getTaxRateById($product->default_sales_tax_profile_id);
        }

        if ($type === 'purchases' && $product->default_purchase_tax_profile_id) {
            return $this->getTaxRateById($product->default_purchase_tax_profile_id);
        }

        // Category specific override
        if ($product->category) {
            if ($type === 'sales' && $product->category->default_sales_tax_profile_id) {
                return $this->getTaxRateById($product->category->default_sales_tax_profile_id);
            }
            if ($type === 'purchases' && $product->category->default_purchase_tax_profile_id) {
                return $this->getTaxRateById($product->category->default_purchase_tax_profile_id);
            }
        }

        // Global default based on type
        return $this->getDefaultTaxRate($type === 'sales' ? 'VAT' : 'VAT'); // Defaulting to VAT for both if enabled
    }

    /**
     * Calculate tax for an amount based on a given tax rate.
     */
    public function calculateTax(float $amount, ?TaxRate $taxRate, bool $isInclusive = false)
    {
        if (!$taxRate || !$taxRate->is_active) {
            return [
                'taxable_amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'tax_rate_id' => null,
                'tax_code' => null,
                'rate' => 0
            ];
        }

        $rate = floatval($taxRate->rate);

        if ($isInclusive) {
            // Formula: Tax = Total - (Total / (1 + Rate))
            // Taxable Amount = Total - Tax
            $taxableAmount = $amount / (1 + ($rate / 100));
            $taxAmount = $amount - $taxableAmount;
            $totalAmount = $amount;
        } else {
            // Formula: Tax = Amount * Rate
            // Total = Amount + Tax
            $taxableAmount = $amount;
            $taxAmount = $amount * ($rate / 100);
            $totalAmount = $amount + $taxAmount;
        }

        return [
            'taxable_amount' => round($taxableAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'tax_rate_id' => $taxRate->id,
            'tax_code' => $taxRate->code,
            'rate' => $rate
        ];
    }

    /**
     * Find a Tax Rate by ID, using caching.
     */
    public function getTaxRateById($id)
    {
        return Cache::remember('tax_rate_' . $id, 86400, function () use ($id) {
            return TaxRate::find($id);
        });
    }

    /**
     * Get the default Tax Rate for a specific type (e.g. VAT).
     */
    public function getDefaultTaxRate(string $taxType)
    {
        return Cache::remember('default_tax_rate_' . $taxType, 86400, function () use ($taxType) {
            return TaxRate::where('tax_type', $taxType)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
        });
    }
}
