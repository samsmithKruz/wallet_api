<?php

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
    Route::get('/protected-test', function () {
        return response()->json([
            'status_code' => 200,
            'message' => 'You have access!',
            'data' => ['protected' => true],
            'errors' => null
        ]);
    });
});
