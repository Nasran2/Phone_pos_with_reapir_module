<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('stock_deducted_at')->nullable()->after('payment_status')->index();
        });

        // Sales created before this migration already reduced inventory in the POS
        // controllers. Mark them finalized so a later payment cannot deduct twice.
        DB::table('sales')
            ->where('status', '!=', 'cancelled')
            ->update(['stock_deducted_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('stock_deducted_at');
        });
    }
};
