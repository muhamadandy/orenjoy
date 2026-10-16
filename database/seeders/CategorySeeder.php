<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['cat_name' => 'Makanan', 'description' => 'Kategori Makanan'],
            ['cat_name' => 'Minuman', 'description' => 'Kategori Minuman'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['cat_name' => $category['cat_name']],
                ['description' => $category['description']]
            );
        }
    }
}
