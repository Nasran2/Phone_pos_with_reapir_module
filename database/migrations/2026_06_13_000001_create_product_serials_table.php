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
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->unique()->index();
            $table->enum('status', ['available', 'sold', 'returned'])->default('available')->index();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};
