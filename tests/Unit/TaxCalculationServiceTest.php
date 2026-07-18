<?php

namespace Tests\Unit;

use App\Services\TaxCalculationService;
use PHPUnit\Framework\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    protected TaxCalculationService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new TaxCalculationService();
    }

    public function test_exclusive_tax_calculation()
    {
        $result = $this->taxService->calculate([
            'unit_price' => 100,
            'quantity' => 2,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'invoice_discount_amount' => 0,
            'tax_rate' => 10, // 10%
            'tax_mode' => 'exclusive',
            'tax_status' => 'taxable',
        ]);

        $this->assertEquals(200, $result['gross_amount']);
        $this->assertEquals(200, $result['taxable_amount']);
        $this->assertEquals(20, $result['added_tax_amount']);
        $this->assertEquals(0, $result['included_tax_amount']);
        $this->assertEquals(20, $result['tax_amount']);
        $this->assertEquals(220, $result['line_total']);
    }

    public function test_inclusive_tax_calculation()
    {
        $result = $this->taxService->calculate([
            'unit_price' => 110,
            'quantity' => 2,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'invoice_discount_amount' => 0,
            'tax_rate' => 10, // 10%
            'tax_mode' => 'inclusive',
            'tax_status' => 'taxable',
        ]);

        $this->assertEquals(220, $result['gross_amount']);
        // 220 / 1.10 = 200
        $this->assertEquals(200, $result['taxable_amount']);
        $this->assertEquals(0, $result['added_tax_amount']);
        $this->assertEquals(20, $result['included_tax_amount']);
        $this->assertEquals(20, $result['tax_amount']);
        $this->assertEquals(220, $result['line_total']);
    }

    public function test_exclusive_tax_with_discount()
    {
        $result = $this->taxService->calculate([
            'unit_price' => 100,
            'quantity' => 2,
            'discount_type' => 'fixed',
            'discount_value' => 20, // 20 per item
            'invoice_discount_amount' => 0,
            'tax_rate' => 10, // 10%
            'tax_mode' => 'exclusive',
            'tax_status' => 'taxable',
        ]);

        $this->assertEquals(200, $result['gross_amount']);
        // Discount 20 on line = 20
        $this->assertEquals(180, $result['amount_after_item_discount']);
        $this->assertEquals(180, $result['taxable_amount']);
        // 10% of 180 = 18
        $this->assertEquals(18, $result['added_tax_amount']);
        $this->assertEquals(18, $result['tax_amount']);
        $this->assertEquals(198, $result['line_total']); // 180 + 18
    }

    public function test_inclusive_tax_with_percentage_discount()
    {
        $result = $this->taxService->calculate([
            'unit_price' => 110,
            'quantity' => 2,
            'discount_type' => 'percentage',
            'discount_value' => 10, // 10%
            'invoice_discount_amount' => 0,
            'tax_rate' => 10, // 10%
            'tax_mode' => 'inclusive',
            'tax_status' => 'taxable',
        ]);

        $this->assertEquals(220, $result['gross_amount']);
        // 10% discount on 220 = 22. Amount after discount = 198
        $this->assertEquals(198, $result['amount_after_item_discount']);
        // Taxable = 198 / 1.10 = 180
        $this->assertEquals(180, $result['taxable_amount']);
        $this->assertEquals(18, $result['included_tax_amount']);
        $this->assertEquals(198, $result['line_total']);
    }

    public function test_non_taxable_item()
    {
        $result = $this->taxService->calculate([
            'unit_price' => 100,
            'quantity' => 1,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'invoice_discount_amount' => 0,
            'tax_rate' => 10,
            'tax_mode' => 'exclusive',
            'tax_status' => 'exempt',
        ]);

        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals(100, $result['taxable_amount']);
        $this->assertEquals(100, $result['line_total']);
    }

    public function test_frontend_scenario_inclusive_product()
    {
        // 1. "Rs 1600.00 inclusive product"
        $result = $this->taxService->calculate([
            'unit_price' => 1600,
            'quantity' => 1,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'invoice_discount_amount' => 0,
            'tax_rate' => 18,
            'tax_mode' => 'inclusive',
            'tax_status' => 'taxable',
        ]);
        
        $this->assertEquals(1600, $result['gross_amount']);
        $this->assertEquals(1355.93, $result['taxable_amount']); // 1600 / 1.18 = 1355.9322
        $this->assertEquals(244.07, $result['included_tax_amount']); // 1600 - 1355.93 = 244.07
        $this->assertEquals(0, $result['added_tax_amount']);
        $this->assertEquals(1600, $result['line_total']);
    }

    public function test_resolve_tax_mode()
    {
        // 1. Item override should take precedence
        $this->assertEquals('exclusive', $this->taxService->resolveTaxMode('exclusive', 'inclusive', 'inclusive'));

        // 2. Product override should take precedence over global if no item override
        $this->assertEquals('inclusive', $this->taxService->resolveTaxMode(null, 'inclusive', 'exclusive'));

        // 3. Global should be used if both are empty/null
        $this->assertEquals('exclusive', $this->taxService->resolveTaxMode(null, null, 'exclusive'));
    }
}
