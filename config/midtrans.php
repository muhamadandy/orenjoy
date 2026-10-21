<?php

return [
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    // Batas waktu pembayaran (menit) sejak halaman bayar dibuat.
    'expiry_minutes' => (int) env('MIDTRANS_EXPIRY_MINUTES', 30),

    // Metode yang ditampilkan di Snap, dipisah koma. Kosongkan untuk menampilkan semua metode yang aktif di akun.
    'enabled_payments' => array_values(array_filter(array_map('trim', explode(',', (string) env('MIDTRANS_ENABLED_PAYMENTS', 'gopay'))))),
];
