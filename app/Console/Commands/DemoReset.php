<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Lewati pertanyaan konfirmasi}';

    protected $description = 'Hapus semua data dan isi ulang data contoh (hanya untuk situs demo)';

    public function handle(): int
    {
        // Pengaman: perintah ini menghapus SEMUA tabel. Hanya boleh jalan di situs demo.
        if (! config('orenjoy.demo')) {
            $this->error('Ditolak: ORENJOY_DEMO belum diaktifkan di .env. Perintah ini hanya untuk situs demo.');

            return self::FAILURE;
        }

        if (config('midtrans.is_production')) {
            $this->error('Ditolak: MIDTRANS_IS_PRODUCTION=true. Jangan reset data di lingkungan yang memakai pembayaran asli.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Semua data akan DIHAPUS dan diganti data contoh. Lanjutkan?')) {
            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->call('cache:clear');

        $this->info('Data demo sudah dikembalikan.');

        return self::SUCCESS;
    }
}
