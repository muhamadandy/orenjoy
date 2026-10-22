<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['cat_name' => 'Makanan', 'description' => 'Kategori Makanan'],
            ['cat_name' => 'Minuman', 'description' => 'Kategori Minuman'],
            ['cat_name' => 'Camilan', 'description' => 'Kategori Camilan'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['cat_name' => $category['cat_name']],
                ['description' => $category['description']]
            );
        }
    }
}
