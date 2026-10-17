<?php

return [

    /*
    | Pajak restoran (persen). Dipakai server untuk menghitung pajak tiap baris
    | pesanan, dan dikirim ke JavaScript supaya angka di keranjang sama.
    | Ubah lewat .env: ORENJOY_TAX_PERCENT=10 (isi 0 jika tanpa pajak).
    */
    'tax_percent' => (int) env('ORENJOY_TAX_PERCENT', 10),

];