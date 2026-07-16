<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('repair_charge_items')) {
            Schema::create('repair_charge_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
                $table->string('name');
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_charge_items');
    }
};
