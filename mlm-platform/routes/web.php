<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\TransferController;
use App\Http\Controllers\Web\WithdrawalWebController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () { return view('login'); });
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.process');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->name('register.process');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/wallet', [DashboardController::class, 'wallet'])->name('wallet');
    Route::get('/network', [DashboardController::class, 'network'])->name('network');
    
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
    
    // Withdrawals
    Route::post('/withdrawals', [WithdrawalWebController::class, 'store'])->name('withdrawal.request');
    
    // Shopper Transfer
    Route::middleware('role:shopper')->group(function () {
        Route::get('/shopper/transfer', [TransferController::class, 'index'])->name('shopper.transfer');
        Route::get('/shopper/check-customer', [TransferController::class, 'checkCustomer']);
        Route::post('/shopper/transfer', [TransferController::class, 'process'])->name('shopper.transfer.process');
    });
});
