<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/menu');

// Customer (publik, tanpa login)
Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/meja/{number}', [MenuController::class, 'table'])->whereNumber('number')->name('table');

// Batas 30 permintaan per menit per IP (WiFi restoran biasanya satu IP untuk semua pelanggan).
Route::post('/checkout', [OrderController::class, 'store'])->middleware('throttle:30,1')->name('checkout');
Route::get('/pesanan/{code}', [OrderController::class, 'show'])->name('order.show');

// Login staf
Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
Route::post('/admin/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
Route::post('/admin/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Panel admin (hanya role admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureRole::class.':admin'])
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
    });