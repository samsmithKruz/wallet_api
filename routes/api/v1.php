<?php

use App\Http\Controllers\V1\TransferController;
use App\Http\Controllers\V1\WalletController;
use Illuminate\Support\Facades\Route;

// Welcome route
Route::get('/', function () {
    return response()->json([
        'message' => 'Wallet Management API v1',
        'version' => '1.0.0',
        'documentation' => url('api/v1/documentation')
    ]);
});

Route::middleware('token.auth')->group(function () {
    Route::prefix('wallets')
        ->controller(WalletController::class)
        ->group(function () {
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
            Route::post('/{id}/fund', 'fund');
            Route::post('/{id}/withdraw', 'withdraw');
            Route::delete('/{id}', 'destroy');
        });

    Route::get('/wallets/{walletId}/transfers', [TransferController::class, 'walletTransfers']);

    Route::prefix('transfers')
        ->controller(TransferController::class)
        ->group(function () {
            Route::post('/', 'store');
            Route::get('/{id}', 'show');
        });
});
