<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, EUR, LKR
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 15, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description');
            $table->decimal('customs_duty_rate', 8, 4)->default(0);
            $table->decimal('pal_rate', 8, 4)->default(0);
            $table->decimal('cess_rate', 8, 4)->default(0);
            $table->decimal('srid_rate', 8, 4)->default(0); // Social Security Levy etc.
            $table->decimal('sscl_rate', 8, 4)->default(0); // Optional extra tax
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('hs_code_id')->nullable()->constrained('hs_codes')->nullOnDelete();
            $table->decimal('weight_kg', 10, 4)->default(0)->after('stock_alert_quantity'); // Needed for weight-based freight allocation
        });

        Schema::create('import_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique(); // E.g. IMP-2023-0001
            $table->date('shipment_date');
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 15, 6);
            $table->decimal('foreign_total_amount', 15, 4)->default(0);
            $table->decimal('local_total_amount', 15, 2)->default(0);
            $table->decimal('total_freight_local', 15, 2)->default(0);
            $table->decimal('total_insurance_local', 15, 2)->default(0);
            $table->decimal('total_clearing_local', 15, 2)->default(0);
            $table->decimal('total_other_costs_local', 15, 2)->default(0);
            $table->decimal('total_customs_levies_local', 15, 2)->default(0);
            $table->decimal('total_landed_cost_local', 15, 2)->default(0); // Total local cost of the shipment
            $table->string('cost_allocation_method')->default('value'); // 'value', 'weight', 'quantity'
            $table->string('status')->default('draft'); // 'draft', 'shipped', 'cleared', 'completed', 'cancelled'
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete(); // Where the stock goes
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('import_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('hs_code_id')->nullable()->constrained('hs_codes')->nullOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('foreign_unit_price', 15, 4);
            $table->decimal('foreign_line_total', 15, 4);
            $table->decimal('local_line_total', 15, 2); // foreign_line_total * exchange_rate
            
            // Allocated Costs
            $table->decimal('allocated_freight', 15, 2)->default(0);
            $table->decimal('allocated_insurance', 15, 2)->default(0);
            $table->decimal('allocated_clearing', 15, 2)->default(0);
            $table->decimal('allocated_other', 15, 2)->default(0);
            
            // Computed Levies
            $table->decimal('customs_duty_amount', 15, 2)->default(0);
            $table->decimal('pal_amount', 15, 2)->default(0);
            $table->decimal('cess_amount', 15, 2)->default(0);
            $table->decimal('srid_amount', 15, 2)->default(0);
            $table->decimal('sscl_amount', 15, 2)->default(0);
            
            // Final Costing
            $table->decimal('landed_unit_cost_local', 15, 2)->default(0); 
            $table->decimal('total_landed_cost_local', 15, 2)->default(0);
            
            $table->timestamps();
        });

        Schema::create('import_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('cost_type'); // 'freight', 'insurance', 'clearing', 'other'
            $table->decimal('amount_local', 15, 2)->default(0);
            $table->string('allocation_method')->default('value'); // 'value', 'weight', 'quantity'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_cost_allocations');
        Schema::dropIfExists('import_shipment_items');
        Schema::dropIfExists('import_shipments');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['hs_code_id']);
            $table->dropColumn(['hs_code_id', 'weight_kg']);
        });

        Schema::dropIfExists('hs_codes');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
