<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'variant_id')) {
                $table->foreignId('variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'variant_id')) {
                $table->dropConstrainedForeignId('variant_id');
            }
        });
    }
};
