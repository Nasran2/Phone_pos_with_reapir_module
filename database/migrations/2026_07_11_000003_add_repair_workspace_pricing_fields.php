<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('repair_jobs', 'diagnosis_fee')) {
                $table->decimal('diagnosis_fee', 15, 2)->default(0)->after('labor_charge');
            }
            if (!Schema::hasColumn('repair_jobs', 'service_charge')) {
                $table->decimal('service_charge', 15, 2)->default(0)->after('diagnosis_fee');
            }
            if (!Schema::hasColumn('repair_jobs', 'software_charge')) {
                $table->decimal('software_charge', 15, 2)->default(0)->after('service_charge');
            }
            if (!Schema::hasColumn('repair_jobs', 'other_charge')) {
                $table->decimal('other_charge', 15, 2)->default(0)->after('software_charge');
            }
            if (!Schema::hasColumn('repair_jobs', 'discount_type')) {
                $table->string('discount_type', 20)->default('fixed')->after('discount_amount');
            }
            if (!Schema::hasColumn('repair_jobs', 'discount_value')) {
                $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repair_jobs', function (Blueprint $table) {
            foreach (['discount_value', 'discount_type', 'other_charge', 'software_charge', 'service_charge', 'diagnosis_fee'] as $column) {
                if (Schema::hasColumn('repair_jobs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
