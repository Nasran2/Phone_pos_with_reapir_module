<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->timestamp('cleared_at')->nullable();
        });
        DB::table('repair_payments')->where('status', 'cleared')->where('payment_method', '!=', 'Cheque')->update(['cleared_at' => DB::raw('payment_date')]);
        DB::table('repair_payments')->where('status', 'cleared')->where('payment_method', 'Cheque')->update(['cleared_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        // Retain receipt dates needed to reconcile historical collection reports.
    }
};
