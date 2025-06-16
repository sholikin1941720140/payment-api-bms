<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

Route::middleware('sec.token')->group(function () {
    Route::get('/order', [PaymentController::class, 'order']);
    Route::get('/payment', [PaymentController::class, 'payment']);
    Route::get('/status', [PaymentController::class, 'checkStatus']);
});