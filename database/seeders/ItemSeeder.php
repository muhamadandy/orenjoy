<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $makanan = Category::where('cat_name', 'Makanan')->value('id');
        $minuman = Category::where('cat_name', 'Minuman')->value('id');

        $items = [
            ['name' => 'Nasi Goreng Spesial', 'description' => 'Nasi goreng dengan telur, ayam, dan kerupuk.', 'price' => 25000, 'category_id' => $makanan],
            ['name' => 'Mie Ayam', 'description' => 'Mie dengan ayam cincang dan pangsit.', 'price' => 20000, 'category_id' => $makanan],
            ['name' => 'Es Teh Manis', 'description' => 'Teh dingin dengan gula.', 'price' => 6000, 'category_id' => $minuman],
            ['name' => 'Jus Jeruk', 'description' => 'Jeruk segar tanpa pengawet.', 'price' => 12000, 'category_id' => $minuman],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}