<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Stores Table
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('manager')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Insert Default Main Store
        $mainStoreId = DB::table('stores')->insertGetId([
            'name' => 'Main Store',
            'code' => 'MS01',
            'phone' => '0000000000',
            'email' => 'main@store.com',
            'address' => 'Main Headquarters',
            'is_default' => true,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Product Store Stock Table (replaces product_stock)
        Schema::create('product_store_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->decimal('minimum_stock', 15, 2)->default(5.00);
            $table->decimal('maximum_stock', 15, 2)->nullable();
            $table->timestamp('last_purchase')->nullable();
            $table->timestamp('last_sale')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);
        });

        // Migrate existing product_stock data to product_store_stock under Main Store
        if (Schema::hasTable('product_stock')) {
            $existingStock = DB::table('product_stock')->get();
            foreach ($existingStock as $stock) {
                DB::table('product_store_stock')->insert([
                    'product_id' => $stock->product_id,
                    'store_id' => $mainStoreId,
                    'quantity' => $stock->quantity,
                    'created_at' => $stock->created_at ?? now(),
                    'updated_at' => $stock->updated_at ?? now(),
                ]);
            }
            // Drop old table
            Schema::dropIfExists('product_stock');
        }

        // 3. Stock Transfers Table
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->foreignId('from_store_id')->constrained('stores');
            $table->foreignId('to_store_id')->constrained('stores');
            $table->date('transfer_date');
            $table->enum('status', ['Pending', 'Approved', 'Completed', 'Cancelled'])->default('Pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 4. Stock Transfer Items Table
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 2);
            $table->timestamps();
        });

        // 5. Barcode Templates Table
        Schema::create('barcode_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('paper_size')->default('A4'); // A4, 40x30, 50x25 etc.
            $table->json('content_settings')->nullable(); // Logo, Name, SKU, Price etc.
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 6. Barcode Print Logs Table
        Schema::create('barcode_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 7. Add store_id to existing transaction tables
        $tablesToUpdate = ['sales', 'purchases', 'stock_adjustments', 'damaged_stocks'];
        foreach ($tablesToUpdate as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($mainStoreId) {
                    $table->foreignId('store_id')->default($mainStoreId)->after('id')->constrained('stores');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesToUpdate = ['sales', 'purchases', 'stock_adjustments', 'damaged_stocks'];
        foreach ($tablesToUpdate as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'store_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['store_id']);
                    $table->dropColumn('store_id');
                });
            }
        }

        Schema::dropIfExists('barcode_print_logs');
        Schema::dropIfExists('barcode_templates');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        
        // Restore product_stock if needed
        Schema::create('product_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->timestamps();
        });
        
        Schema::dropIfExists('product_store_stock');
        Schema::dropIfExists('stores');
    }
};
