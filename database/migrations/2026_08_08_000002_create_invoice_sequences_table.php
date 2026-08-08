<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('pos_type', 20);
            $table->date('sequence_date');
            $table->string('prefix', 20);
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['pos_type', 'sequence_date', 'prefix'], 'invoice_sequences_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
