<?php

namespace Database\Seeders;

use App\Support\Features;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles and Permissions are now handled by UserManagementSeeder.

        // 2. Categories, Brands, Units
        $categories = [
            ['id' => 1, 'name' => 'Smartphones', 'slug' => 'smartphones', 'description' => 'Mobile phones', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Chargers, cases, cables', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('categories')->insert($categories);

        $brands = [
            ['id' => 1, 'name' => 'Apple', 'slug' => 'apple', 'description' => 'Apple Inc.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Samsung', 'slug' => 'samsung', 'description' => 'Samsung Electronics', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('brands')->insert($brands);

        // Units are handled by UnitSeeder

        // 3. Products
        $products = [
            [
                'id' => 1, 'category_id' => 2, 'brand_id' => 1, 'unit_id' => 1,
                'name' => 'iPhone 13 Pro Case', 'sku' => 'IP13-CASE', 'barcode' => '111111111111',
                'cost_price' => 500.00, 'retail_price' => 1200.00, 'wholesale_price' => 900.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 2, 'category_id' => 2, 'brand_id' => 2, 'unit_id' => 1,
                'name' => 'Samsung Charger 25W', 'sku' => 'SAM-CHG-25W', 'barcode' => '222222222222',
                'cost_price' => 800.00, 'retail_price' => 1800.00, 'wholesale_price' => 1400.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 3, 'category_id' => 2, 'brand_id' => 2, 'unit_id' => 1,
                'name' => 'USB Type-C Cable', 'sku' => 'USB-C-CBL', 'barcode' => '333333333333',
                'cost_price' => 200.00, 'retail_price' => 600.00, 'wholesale_price' => 450.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 4, 'category_id' => 2, 'brand_id' => 1, 'unit_id' => 1,
                'name' => 'AirPods Pro', 'sku' => 'AIRPODS-PRO', 'barcode' => '444444444444',
                'cost_price' => 12000.00, 'retail_price' => 24500.00, 'wholesale_price' => 21000.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 5, 'category_id' => 2, 'brand_id' => 1, 'unit_id' => 1,
                'name' => 'Tempered Glass', 'sku' => 'TEMP-GLS', 'barcode' => '555555555555',
                'cost_price' => 100.00, 'retail_price' => 400.00, 'wholesale_price' => 250.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 6, 'category_id' => 1, 'brand_id' => 1, 'unit_id' => 1,
                'name' => 'iPhone 15 Pro Max 256GB', 'sku' => 'IP15PM-256', 'barcode' => '666666666666',
                'cost_price' => 180000.00, 'retail_price' => 245000.00, 'wholesale_price' => 224000.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 7, 'category_id' => 1, 'brand_id' => 2, 'unit_id' => 1,
                'name' => 'Samsung Galaxy S24 Ultra 512GB', 'sku' => 'SAM-S24U-512', 'barcode' => '777777777777',
                'cost_price' => 150000.00, 'retail_price' => 210000.00, 'wholesale_price' => 189000.00,
                'stock_alert_quantity' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()
            ]
        ];
        DB::table('products')->insert($products);
 
        // 2.5 Default Store
        $stores = [
            ['id' => 1, 'code' => 'MAIN', 'name' => 'Main Store', 'status' => 1, 'is_default' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('stores')->insertOrIgnore($stores);

        // Product Stocks
        $stocks = [
            ['product_id' => 1, 'store_id' => 1, 'quantity' => 3.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'store_id' => 1, 'quantity' => 2.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 3, 'store_id' => 1, 'quantity' => 4.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 4, 'store_id' => 1, 'quantity' => 1.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 5, 'store_id' => 1, 'quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 6, 'store_id' => 1, 'quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 7, 'store_id' => 1, 'quantity' => 4.00, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('product_store_stock')->insert($stocks);

        // Product Serials / IMEIs
        $serials = [
            ['product_id' => 6, 'serial_number' => 'IMEI-15PM-001', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 6, 'serial_number' => 'IMEI-15PM-002', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 6, 'serial_number' => 'IMEI-15PM-003', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 6, 'serial_number' => 'IMEI-15PM-004', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 6, 'serial_number' => 'IMEI-15PM-005', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            
            ['product_id' => 7, 'serial_number' => 'IMEI-S24U-001', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 7, 'serial_number' => 'IMEI-S24U-002', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 7, 'serial_number' => 'IMEI-S24U-003', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 7, 'serial_number' => 'IMEI-S24U-004', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('product_serials')->insert($serials);

        // 4. Customers & Suppliers
        $customers = [
            ['id' => 1, 'name' => 'Walk-in Customer', 'phone' => 'N/A', 'balance' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Mr. Nimal Perera', 'phone' => '0771234567', 'balance' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Saman Mobile Store', 'phone' => '0719876543', 'balance' => 25800.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Tech Solutions (Pvt) Ltd', 'phone' => '0112345678', 'balance' => 48500.00, 'created_at' => now(), 'updated_at' => now()],
        ];
        if (! Features::wholesaleEnabled()) {
            foreach ($customers as &$customer) {
                $customer['balance'] = 0;
            }
            unset($customer);
        }
        DB::table('customers')->insert($customers);

        $suppliers = [
            ['id' => 1, 'name' => 'Lanka Mobile (Pvt) Ltd', 'company_name' => 'Lanka Mobile', 'balance' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Global Electronics', 'company_name' => 'Global Electronics', 'balance' => 45230.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Smart Technologies', 'company_name' => 'Smart Tech', 'balance' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Future Mobile Parts', 'company_name' => 'Future Parts', 'balance' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Mega International', 'company_name' => 'Mega Int', 'balance' => 65420.00, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('suppliers')->insert($suppliers);

        // 5. Sales (INV-000121 to INV-000125)
        $today = date('Y-m-d');
        $sales = [
            [
                'id' => 1, 'customer_id' => 4, 'invoice_no' => 'INV-000121', 'sale_date' => $today,
                'pos_type' => 'wholesale', 'total_amount' => 48500.00, 'payable_amount' => 48500.00,
                'paid_amount' => 0.00, 'due_amount' => 48500.00, 'total_cost_amount' => 30000.00,
                'total_profit_amount' => 18500.00, 'status' => 'final', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 2, 'customer_id' => 1, 'invoice_no' => 'INV-000122', 'sale_date' => $today,
                'pos_type' => 'retail', 'total_amount' => 6750.00, 'payable_amount' => 6750.00,
                'paid_amount' => 6750.00, 'due_amount' => 0.00, 'total_cost_amount' => 3000.00,
                'total_profit_amount' => 3750.00, 'status' => 'final', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 3, 'customer_id' => 3, 'invoice_no' => 'INV-000123', 'sale_date' => $today,
                'pos_type' => 'wholesale', 'total_amount' => 25800.00, 'payable_amount' => 25800.00,
                'paid_amount' => 0.00, 'due_amount' => 25800.00, 'total_cost_amount' => 15000.00,
                'total_profit_amount' => 10800.00, 'status' => 'final', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 4, 'customer_id' => 2, 'invoice_no' => 'INV-000124', 'sale_date' => $today,
                'pos_type' => 'retail', 'total_amount' => 15600.00, 'payable_amount' => 15600.00,
                'paid_amount' => 15600.00, 'due_amount' => 0.00, 'total_cost_amount' => 8000.00,
                'total_profit_amount' => 7600.00, 'status' => 'final', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 5, 'customer_id' => 1, 'invoice_no' => 'INV-000125', 'sale_date' => $today,
                'pos_type' => 'retail', 'total_amount' => 8950.00, 'payable_amount' => 8950.00,
                'paid_amount' => 8950.00, 'due_amount' => 0.00, 'total_cost_amount' => 4000.00,
                'total_profit_amount' => 4950.00, 'status' => 'final', 'created_at' => now(), 'updated_at' => now()
            ],
        ];
        $sales = array_values(array_filter($sales, fn ($sale) => Features::wholesaleEnabled() || $sale['pos_type'] !== 'wholesale'));
        DB::table('sales')->insert($sales);

        $saleItems = [
            [
                'sale_id' => 1, 'product_id' => 7, 'quantity' => 1, 'cost_price' => 30000.00,
                'selling_price' => 48500.00, 'unit_price' => 48500.00, 'price_type' => 'wholesale',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 48500.00,
                'profit_amount' => 18500.00, 'subtotal' => 48500.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 2, 'product_id' => 1, 'quantity' => 3, 'cost_price' => 500.00,
                'selling_price' => 1200.00, 'unit_price' => 1200.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 3600.00,
                'profit_amount' => 2100.00, 'subtotal' => 3600.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 2, 'product_id' => 2, 'quantity' => 1, 'cost_price' => 800.00,
                'selling_price' => 1800.00, 'unit_price' => 1800.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 1800.00,
                'profit_amount' => 1000.00, 'subtotal' => 1800.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 2, 'product_id' => 3, 'quantity' => 2, 'cost_price' => 200.00,
                'selling_price' => 675.00, 'unit_price' => 675.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 1350.00,
                'profit_amount' => 950.00, 'subtotal' => 1350.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 3, 'product_id' => 4, 'quantity' => 1, 'cost_price' => 15000.00,
                'selling_price' => 25800.00, 'unit_price' => 25800.00, 'price_type' => 'wholesale',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 25800.00,
                'profit_amount' => 10800.00, 'subtotal' => 25800.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 4, 'product_id' => 2, 'quantity' => 4, 'cost_price' => 800.00,
                'selling_price' => 1800.00, 'unit_price' => 1800.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 7200.00,
                'profit_amount' => 4000.00, 'subtotal' => 7200.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 4, 'product_id' => 5, 'quantity' => 21, 'cost_price' => 228.57,
                'selling_price' => 400.00, 'unit_price' => 400.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 8400.00,
                'profit_amount' => 3600.00, 'subtotal' => 8400.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 5, 'product_id' => 1, 'quantity' => 4, 'cost_price' => 500.00,
                'selling_price' => 1200.00, 'unit_price' => 1200.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 4800.00,
                'profit_amount' => 2800.00, 'subtotal' => 4800.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 5, 'product_id' => 3, 'quantity' => 2, 'cost_price' => 200.00,
                'selling_price' => 600.00, 'unit_price' => 600.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 1200.00,
                'profit_amount' => 800.00, 'subtotal' => 1200.00, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'sale_id' => 5, 'product_id' => 5, 'quantity' => 7.375, 'cost_price' => 216.95,
                'selling_price' => 400.00, 'unit_price' => 400.00, 'price_type' => 'retail',
                'discount_type' => 'fixed', 'discount_amount' => 0, 'line_total' => 2950.00,
                'profit_amount' => 1350.00, 'subtotal' => 2950.00, 'created_at' => now(), 'updated_at' => now(),
            ],
        ];
        DB::table('sale_items')->insert(array_values(array_filter($saleItems, fn ($item) => Features::wholesaleEnabled() || $item['price_type'] !== 'wholesale')));

        // Sales payments
        $salePayments = [
            ['sale_id' => 2, 'payment_date' => $today, 'amount' => 6750.00, 'payment_method' => 'Cash', 'created_at' => now(), 'updated_at' => now()],
            ['sale_id' => 4, 'payment_date' => $today, 'amount' => 15600.00, 'payment_method' => 'Card', 'created_at' => now(), 'updated_at' => now()],
            ['sale_id' => 5, 'payment_date' => $today, 'amount' => 8950.00, 'payment_method' => 'Cash', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('sale_payments')->insert($salePayments);

        // 6. Purchases (PO-000081 to PO-000085)
        $purchases = [
            [
                'id' => 1, 'supplier_id' => 5, 'invoice_no' => 'PO-000081', 'purchase_date' => $today,
                'total_amount' => 65420.00, 'paid_amount' => 0.00, 'due_amount' => 65420.00, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 2, 'supplier_id' => 4, 'invoice_no' => 'PO-000082', 'purchase_date' => $today,
                'total_amount' => 18950.00, 'paid_amount' => 18950.00, 'due_amount' => 0.00, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 3, 'supplier_id' => 3, 'invoice_no' => 'PO-000083', 'purchase_date' => $today,
                'total_amount' => 36850.00, 'paid_amount' => 36850.00, 'due_amount' => 0.00, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 4, 'supplier_id' => 2, 'invoice_no' => 'PO-000084', 'purchase_date' => $today,
                'total_amount' => 45230.00, 'paid_amount' => 0.00, 'due_amount' => 45230.00, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()
            ],
            [
                'id' => 5, 'supplier_id' => 1, 'invoice_no' => 'PO-000085', 'purchase_date' => $today,
                'total_amount' => 82600.00, 'paid_amount' => 82600.00, 'due_amount' => 0.00, 'status' => 'received', 'created_at' => now(), 'updated_at' => now()
            ],
        ];
        DB::table('purchases')->insert($purchases);

        DB::table('purchase_items')->insert([
            ['purchase_id' => 1, 'product_id' => 6, 'quantity' => 0.25, 'unit_cost' => 180000.00, 'cost_price' => 180000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 45000.00, 'profit_margin' => 0, 'retail_price' => 245000.00, 'wholesale_price' => 224000.00, 'subtotal' => 45000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 1, 'product_id' => 4, 'quantity' => 1, 'unit_cost' => 12000.00, 'cost_price' => 12000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 12000.00, 'profit_margin' => 0, 'retail_price' => 24500.00, 'wholesale_price' => 21000.00, 'subtotal' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 1, 'product_id' => 2, 'quantity' => 5, 'unit_cost' => 1684.00, 'cost_price' => 1684.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 8420.00, 'profit_margin' => 0, 'retail_price' => 1800.00, 'wholesale_price' => 1400.00, 'subtotal' => 8420.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 2, 'product_id' => 1, 'quantity' => 10, 'unit_cost' => 500.00, 'cost_price' => 500.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 5000.00, 'profit_margin' => 0, 'retail_price' => 1200.00, 'wholesale_price' => 900.00, 'subtotal' => 5000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 2, 'product_id' => 2, 'quantity' => 5, 'unit_cost' => 800.00, 'cost_price' => 800.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 4000.00, 'profit_margin' => 0, 'retail_price' => 1800.00, 'wholesale_price' => 1400.00, 'subtotal' => 4000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 2, 'product_id' => 4, 'quantity' => 0.8291667, 'unit_cost' => 12000.00, 'cost_price' => 12000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 9950.00, 'profit_margin' => 0, 'retail_price' => 24500.00, 'wholesale_price' => 21000.00, 'subtotal' => 9950.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 3, 'product_id' => 4, 'quantity' => 2, 'unit_cost' => 12000.00, 'cost_price' => 12000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 24000.00, 'profit_margin' => 0, 'retail_price' => 24500.00, 'wholesale_price' => 21000.00, 'subtotal' => 24000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 3, 'product_id' => 2, 'quantity' => 10, 'unit_cost' => 800.00, 'cost_price' => 800.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 8000.00, 'profit_margin' => 0, 'retail_price' => 1800.00, 'wholesale_price' => 1400.00, 'subtotal' => 8000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 3, 'product_id' => 3, 'quantity' => 24.25, 'unit_cost' => 200.00, 'cost_price' => 200.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 4850.00, 'profit_margin' => 0, 'retail_price' => 600.00, 'wholesale_price' => 450.00, 'subtotal' => 4850.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 4, 'product_id' => 6, 'quantity' => 0.2, 'unit_cost' => 180000.00, 'cost_price' => 180000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 36000.00, 'profit_margin' => 0, 'retail_price' => 245000.00, 'wholesale_price' => 224000.00, 'subtotal' => 36000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 4, 'product_id' => 1, 'quantity' => 10, 'unit_cost' => 500.00, 'cost_price' => 500.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 5000.00, 'profit_margin' => 0, 'retail_price' => 1200.00, 'wholesale_price' => 900.00, 'subtotal' => 5000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 4, 'product_id' => 5, 'quantity' => 42.3, 'unit_cost' => 100.00, 'cost_price' => 100.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 4230.00, 'profit_margin' => 0, 'retail_price' => 400.00, 'wholesale_price' => 250.00, 'subtotal' => 4230.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 5, 'product_id' => 7, 'quantity' => 0.4, 'unit_cost' => 150000.00, 'cost_price' => 150000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 60000.00, 'profit_margin' => 0, 'retail_price' => 210000.00, 'wholesale_price' => 189000.00, 'subtotal' => 60000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 5, 'product_id' => 4, 'quantity' => 1, 'unit_cost' => 12000.00, 'cost_price' => 12000.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 12000.00, 'profit_margin' => 0, 'retail_price' => 24500.00, 'wholesale_price' => 21000.00, 'subtotal' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 5, 'product_id' => 2, 'quantity' => 13.25, 'unit_cost' => 800.00, 'cost_price' => 800.00, 'discount_percent' => 0, 'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 10600.00, 'profit_margin' => 0, 'retail_price' => 1800.00, 'wholesale_price' => 1400.00, 'subtotal' => 10600.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Purchase payments
        $purchasePayments = [
            ['purchase_id' => 2, 'payment_date' => $today, 'amount' => 18950.00, 'payment_method' => 'Cash', 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 3, 'payment_date' => $today, 'amount' => 36850.00, 'payment_method' => 'Bank Transfer', 'created_at' => now(), 'updated_at' => now()],
            ['purchase_id' => 5, 'payment_date' => $today, 'amount' => 82600.00, 'payment_method' => 'Bank Transfer', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('purchase_payments')->insert($purchasePayments);

        // 7. Expenses
        // Expense categories are seeded by AccountingSeeder
        // DB::table('expenses')->insert([
        //     ['id' => 1, 'expense_category_id' => 1, 'amount' => 12450.00, 'expense_date' => $today, 'reference_no' => 'EXP-001', 'note' => 'Today expenses', 'created_at' => now(), 'updated_at' => now()]
        // ]);

        // 8. Daily Summaries
        DB::table('daily_summaries')->insert([
            'date' => $today,
            'total_sales' => Features::wholesaleEnabled() ? 256850.00 : array_sum(array_column($sales, 'payable_amount')),
            'retail_sales' => Features::wholesaleEnabled() ? 145230.00 : array_sum(array_column($sales, 'payable_amount')),
            'wholesale_sales' => Features::wholesaleEnabled() ? 111620.00 : 0,
            'total_purchases' => 182600.00,
            'total_profit' => Features::wholesaleEnabled() ? 58450.00 : array_sum(array_column($sales, 'total_profit_amount')),
            'total_expenses' => 12450.00,
            'customer_due_collected' => 0.00,
            'supplier_due_paid' => 0.00,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Low Stock Alerts
        $lowStockAlerts = [
            ['product_id' => 1, 'current_stock' => 3.00, 'alert_quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'current_stock' => 2.00, 'alert_quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 3, 'current_stock' => 4.00, 'alert_quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 4, 'current_stock' => 1.00, 'alert_quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 5, 'current_stock' => 5.00, 'alert_quantity' => 5.00, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('low_stock_alerts')->insert($lowStockAlerts);
    }
}
