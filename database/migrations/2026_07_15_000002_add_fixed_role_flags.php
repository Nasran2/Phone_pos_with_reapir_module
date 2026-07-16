<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('is_system_role')->default(false);
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_deletable')->default(true);
            $table->boolean('is_renamable')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('roles', fn (Blueprint $table) => $table->dropColumn(['slug','is_system_role','is_fixed','is_deletable','is_renamable']));
    }
};
