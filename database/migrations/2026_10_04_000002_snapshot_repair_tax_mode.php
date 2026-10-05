<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->string('tax_mode')->default('exclusive');
        });
    }

    public function down(): void
    {
        // Keep the saved financial calculation mode with historical jobs.
    }
};
