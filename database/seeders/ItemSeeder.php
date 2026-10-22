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
        $camilan = Category::where('cat_name', 'Camilan')->value('id');

        $items = [
            ['name' => 'Nasi Goreng Spesial', 'description' => 'Nasi goreng dengan telur, ayam, dan kerupuk.', 'price' => 25000, 'category_id' => $makanan],
            ['name' => 'Mie Ayam', 'description' => 'Mie dengan ayam cincang dan pangsit.', 'price' => 20000, 'category_id' => $makanan],
            ['name' => 'Ayam Geprek', 'description' => 'Ayam goreng tepung dengan sambal bawang pedas.', 'price' => 22000, 'category_id' => $makanan],
            ['name' => 'Sate Ayam', 'description' => 'Sepuluh tusuk sate ayam dengan bumbu kacang.', 'price' => 28000, 'category_id' => $makanan],
            ['name' => 'Soto Ayam', 'description' => 'Soto kuah bening dengan suwiran ayam dan telur.', 'price' => 21000, 'category_id' => $makanan],
            ['name' => 'Gado-gado', 'description' => 'Sayuran rebus dengan bumbu kacang dan lontong.', 'price' => 18000, 'category_id' => $makanan],

            ['name' => 'Es Teh Manis', 'description' => 'Teh dingin dengan gula.', 'price' => 6000, 'category_id' => $minuman],
            ['name' => 'Jus Jeruk', 'description' => 'Jeruk segar tanpa pengawet.', 'price' => 12000, 'category_id' => $minuman],
            ['name' => 'Kopi Susu', 'description' => 'Kopi dengan susu dan gula aren.', 'price' => 15000, 'category_id' => $minuman],
            ['name' => 'Es Jeruk', 'description' => 'Perasan jeruk dengan es batu.', 'price' => 10000, 'category_id' => $minuman],
            ['name' => 'Air Mineral', 'description' => 'Air mineral botol 600 ml.', 'price' => 5000, 'category_id' => $minuman],

            ['name' => 'Kentang Goreng', 'description' => 'Kentang goreng renyah dengan saus sambal.', 'price' => 15000, 'category_id' => $camilan],
            ['name' => 'Pisang Goreng', 'description' => 'Pisang goreng tepung, disajikan hangat.', 'price' => 12000, 'category_id' => $camilan],
            ['name' => 'Tahu Crispy', 'description' => 'Tahu goreng tepung dengan cabai rawit.', 'price' => 13000, 'category_id' => $camilan],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
