<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Template admin (Mazer) memakai Bootstrap 5, jadi nomor halaman harus ikut Bootstrap.
        Paginator::useBootstrapFive();
    }
}
