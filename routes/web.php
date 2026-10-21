<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiningTableController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Staff\OrderController as StaffOrderController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/menu');

// Customer (publik, tanpa login)
Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/meja/{token}', [MenuController::class, 'table'])->where('token', '[a-z0-9]{24}')->name('table');

// Batas 30 permintaan per menit per IP (WiFi restoran biasanya satu IP untuk semua pelanggan).
Route::post('/checkout', [OrderController::class, 'store'])->middleware('throttle:30,1')->name('checkout');
Route::get('/pesanan/{code}', [OrderController::class, 'show'])->name('order.show');

// Login staf
Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
Route::post('/admin/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
Route::post('/admin/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Pesanan: admin, kasir, dan koki (hak ubah status diatur di App\Support\OrderFlow)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureRole::class.':admin,cashier,chef'])
    ->group(function () {
        Route::get('orders', [StaffOrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}/status', [StaffOrderController::class, 'updateStatus'])->name('orders.status');
    });

// Input pesanan oleh kasir (admin dan kasir saja, koki tidak)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureRole::class.':admin,cashier'])
    ->group(function () {
        Route::get('orders/create', [StaffOrderController::class, 'create'])->name('orders.create');
        Route::post('orders', [StaffOrderController::class, 'store'])->name('orders.store');
    });

// Panel admin (hanya role admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureRole::class.':admin'])
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('categories', CategoryController::class)->except('show');

        Route::patch('items/{item}/toggle', [ItemController::class, 'toggle'])->name('items.toggle');
        Route::resource('items', ItemController::class)->except('show');

        Route::get('tables/print', [DiningTableController::class, 'print'])->name('tables.print');
        Route::patch('tables/{table}/toggle', [DiningTableController::class, 'toggle'])->name('tables.toggle');
        Route::post('tables/{table}/regenerate', [DiningTableController::class, 'regenerate'])->name('tables.regenerate');
        Route::resource('tables', DiningTableController::class)->only(['index', 'store', 'destroy']);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::resource('users', UserController::class)->except('show');
    });
