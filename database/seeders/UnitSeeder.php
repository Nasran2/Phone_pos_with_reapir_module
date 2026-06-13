<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'short_name' => 'pcs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Box', 'short_name' => 'box', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pack', 'short_name' => 'pack', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meter', 'short_name' => 'm', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(
                ['short_name' => $unit['short_name']],
                $unit
            );
        }
    }
}
