<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        // Meja 1-10 untuk contoh. Aman dijalankan berulang (tidak menimpa token).
        foreach (range(1, 10) as $number) {
            DiningTable::firstOrCreate(['number' => $number]);
        }
    }
}
