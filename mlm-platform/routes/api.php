<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ShopperController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/verify-payment', [AuthController::class, 'verifyOtpAndPay']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User Profile
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Wallet
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

    // Shopper Actions
    Route::prefix('shopper')->group(function () {
        Route::get('/search-customer', [ShopperController::class, 'searchCustomer']);
        Route::post('/transfer', [ShopperController::class, 'transfer']);
    });

    // Withdrawals
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);

    Route::get('/mlm/downline', [\App\Http\Controllers\Api\MlmController::class, 'downline']);
    Route::get('/mlm/stats', [\App\Http\Controllers\Api\MlmController::class, 'teamIncomeStats']);

    // Clubs
    Route::get('/clubs', [ClubController::class, 'index']);
    Route::get('/clubs/income', [ClubController::class, 'incomeHistory']);
    Route::get('/clubs/global-stats', [ClubController::class, 'globalStats']);

    // Branch Manager
    Route::prefix('branch')->group(function () {
        Route::get('/stats', [BranchManagerController::class, 'stats']);
        Route::post('/fund-shopper', [BranchManagerController::class, 'fundShopper']);
    });

});
