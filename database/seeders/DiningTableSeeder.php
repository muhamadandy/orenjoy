<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        $demo = (bool) config('orenjoy.demo');

        // Meja 1-10 untuk contoh.
        foreach (range(1, 10) as $number) {
            if ($demo) {
                // Mode demo: token tetap supaya tautan meja di README tidak berubah setelah demo:reset.
                // Token ini bisa ditebak, jadi hanya dipakai saat ORENJOY_DEMO=true.
                DiningTable::updateOrCreate(
                    ['number' => $number],
                    ['token' => self::demoToken($number)],
                );
            } else {
                // Mode biasa: token acak dari model, tidak menimpa token yang sudah ada.
                DiningTable::firstOrCreate(['number' => $number]);
            }
        }
    }

    /** Token demo: tepat 24 karakter (huruf kecil + angka), sesuai aturan route /meja/{token}. */
    public static function demoToken(int $number): string
    {
        return 'orenjoydemomeja'.sprintf('%09d', $number);
    }
}
