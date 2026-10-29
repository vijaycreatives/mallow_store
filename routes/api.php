<?php

use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Mallow Store
|--------------------------------------------------------------------------
*/

// Create a new order
Route::post('/orders', [OrderController::class, 'store']);

// Fetch a customer's order history by email
Route::get('/customers/{email}/orders', [CustomerOrderController::class, 'index'])
    ->where('email', '.+'); // allow dots in email address

// Products below the low-stock threshold
Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
