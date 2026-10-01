<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Support\SystemSettings;

test('invoice downloads are A4 PDFs without an extra signature page', function (string $posType, int $itemCount) {
    $user = createUserWithRole('Super Admin');
    SystemSettings::clearCache();
    $customer = Customer::create(['name' => 'Invoice Customer', 'phone' => '0771234567']);
    $product = Product::create([
        'name' => 'Invoice Phone',
        'sku' => 'PDF-PHONE',
        'cost_price' => 200,
        'retail_price' => 350,
        'wholesale_price' => 350,
        'is_active' => true,
    ]);
    $sale = Sale::create([
        'customer_id' => $customer->id,
        'invoice_no' => 'PDF-202609300001',
        'sale_date' => today(),
        'pos_type' => $posType,
        'total_amount' => 1750 * $itemCount,
        'payable_amount' => 1750 * $itemCount,
        'paid_amount' => 0,
        'due_amount' => 1750 * $itemCount,
        'payment_status' => 'Due',
        'status' => 'final',
        'store_id' => 1,
        'created_by' => $user->id,
    ]);
    for ($index = 0; $index < $itemCount; $index++) {
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'cost_price' => 200,
            'selling_price' => 350,
            'subtotal' => 1750,
            'profit_amount' => 750,
        ]);
    }
    $sale->invoicePayments()->create([
        'payment_method' => 'Customer Credit / Due',
        'amount' => $sale->payable_amount,
        'status' => 'due',
    ]);

    $response = $this->actingAs($user)->get(route('sales.download', $sale));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain($sale->invoice_no.'.pdf');
    $pdf = $response->getContent();
    expect($pdf)->toStartWith('%PDF-');
    preg_match_all('~/Type\s*/Page\b~', $pdf, $pages);
    if ($itemCount === 1) {
        expect($pages[0])->toHaveCount(1);
    } else {
        expect(count($pages[0]))->toBeGreaterThan(1);
    }
    expect($pdf)->toMatch('~/MediaBox\s*\[0(?:\.\d+)?\s+0(?:\.\d+)?\s+595\.\d+\s+841\.\d+\]~');
})->with([
    'retail' => ['retail', 1],
    'wholesale' => ['wholesale', 1],
    'long wholesale invoice' => ['wholesale', 40],
]);
