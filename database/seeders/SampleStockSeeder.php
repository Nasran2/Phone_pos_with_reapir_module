<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SampleStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $store = Store::where('is_default', true)->first();
        
        if (!$store) {
            $store = Store::first();
        }

        if (!$store) {
            $store = Store::create([
                'name' => 'Main Store',
                'code' => 'MAIN',
                'is_default' => true,
                'status' => 'active',
            ]);
        }

        $products = Product::all();

        foreach ($products as $product) {
            $stock = ProductStoreStock::firstOrNew([
                'product_id' => $product->id,
                'store_id' => $store->id,
            ]);

            $stock->quantity = rand(20, 100);
            $stock->minimum_stock = 10;
            $stock->save();
            
            // Just in case it's using the old product_stock table for something else
            // we will also make sure we don't break anything, though ProductStoreStock is clearly used based on code
        }
    }
}
