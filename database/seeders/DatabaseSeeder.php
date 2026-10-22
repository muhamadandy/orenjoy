<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            DiningTableSeeder::class,
        ]);

        // Pesanan contoh hanya untuk situs demo (ORENJOY_DEMO=true di .env).
        if (config('orenjoy.demo')) {
            $this->call(DemoOrderSeeder::class);
        }
    }
}
