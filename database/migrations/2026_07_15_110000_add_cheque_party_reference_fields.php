<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('account_number');
            $table->string('received_from')->nullable()->after('payee_name');
            $table->string('issued_to')->nullable()->after('received_from');
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'received_from', 'issued_to']);
        });
    }
};
