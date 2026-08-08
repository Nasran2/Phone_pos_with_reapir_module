<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Smartphones' => [
                'iPhone 15 Pro',
                'iPhone 15',
                'Samsung Galaxy S24 Ultra',
                'Samsung Galaxy S24',
                'Google Pixel 8 Pro',
                'Google Pixel 8',
                'OnePlus 12',
            ],
            'Accessories' => [
                'Apple AirPods Pro 2',
                'Apple 20W USB-C Power Adapter',
                'Samsung Galaxy Buds 2 Pro',
                'Anker PowerCore 10000mAh',
                'Spigen Liquid Air Case',
                'Belkin MagSafe Charger',
                'JBL Flip 6',
            ],
            'Tablets' => [
                'iPad Pro 12.9',
                'iPad Air 5th Gen',
                'Samsung Galaxy Tab S9',
                'Lenovo Tab P11',
                'Microsoft Surface Pro 9',
            ],
            'Laptops' => [
                'MacBook Pro M3 14"',
                'MacBook Air M3 15"',
                'Dell XPS 15',
                'Lenovo ThinkPad X1 Carbon',
                'Asus ROG Zephyrus G14',
                'HP Spectre x360',
                'Acer Swift 3',
            ],
        ];

        foreach ($categories as $categoryName => $products) {
            $category = Category::firstOrCreate([
                'slug' => Str::slug($categoryName)
            ], [
                'name' => $categoryName,
                'description' => $categoryName . ' category',
            ]);

            foreach ($products as $index => $productName) {
                $basePrice = rand(50, 1500);
                $costPrice = $basePrice;
                $wholesalePrice = $basePrice * 1.1;
                $retailPrice = $basePrice * 1.3;

                Product::create([
                    'category_id' => $category->id,
                    'name' => $productName,
                    'sku' => Str::slug($productName) . '-' . rand(1000, 9999),
                    'barcode' => rand(100000000000, 999999999999),
                    'description' => 'Sample description for ' . $productName,
                    'cost_price' => $costPrice,
                    'wholesale_price' => $wholesalePrice,
                    'retail_price' => $retailPrice,
                    'stock_alert_quantity' => rand(5, 20),
                    'is_active' => true,
                ]);
            }
        }
    }
}
