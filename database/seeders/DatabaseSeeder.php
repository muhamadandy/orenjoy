<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,      // 1. harus pertama (dipakai users)
            UserSeeder::class,      // 2. butuh roles
            CategorySeeder::class,  // 3. harus sebelum items
            ItemSeeder::class,      // 4. butuh categories
        ]);
    }
}
