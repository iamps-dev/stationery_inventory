<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 👉 Import Controllers
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;




// ======================================================
// 🔐 OPTIONAL: Auth User
// ======================================================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ======================================================
// 📦 API RESOURCES (🔥 CLEAN & PROFESSIONAL)
// ======================================================

Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('purchases', PurchaseController::class);
Route::apiResource('sales', SaleController::class);