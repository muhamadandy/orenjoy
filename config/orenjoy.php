<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pajak restoran (persen)
    |--------------------------------------------------------------------------
    | Dipakai untuk menghitung pajak tiap baris pesanan di server. Nilai ini
    | juga dikirim ke JavaScript supaya angka di keranjang sama dengan hasil
    | akhir. Ubah lewat .env: ORENJOY_TAX_PERCENT=10 (isi 0 jika tanpa pajak).
    */
    'tax_percent' => (int) env('ORENJOY_TAX_PERCENT', 10),

    /*
    |--------------------------------------------------------------------------
    | Masa berlaku scan QR meja (jam)
    |--------------------------------------------------------------------------
    | Setelah scan QR, HP pelanggan "ingat" mejanya selama waktu ini. Lewat
    | dari itu pelanggan harus scan QR di meja lagi. Dihitung dari scan
    | terakhir. Ubah lewat .env: ORENJOY_TABLE_SESSION_HOURS=3
    */
    'table_session_hours' => (int) env('ORENJOY_TABLE_SESSION_HOURS', 3),

    /*
    |--------------------------------------------------------------------------
    | Meja dianggap terisi (menit)
    |--------------------------------------------------------------------------
    | Di form pesanan kasir, meja yang punya pesanan dalam waktu ini ditandai
    | "terisi". Ubah lewat .env: ORENJOY_TABLE_OCCUPIED_MINUTES=90
    */
    'table_occupied_minutes' => (int) env('ORENJOY_TABLE_OCCUPIED_MINUTES', 90),

    // Mode situs demo: mengaktifkan pesanan contoh dan perintah demo:reset.
    'demo' => (bool) env('ORENJOY_DEMO', false),
];