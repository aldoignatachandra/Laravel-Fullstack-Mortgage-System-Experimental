<?php

use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\MortgageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the Illuminate\Routing\Router within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authenticated endpoints
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', CurrentUserController::class);

    // Mortgages (customer actions)
    Route::get('/mortgages', [MortgageController::class, 'index']);
    Route::post('/mortgages', [MortgageController::class, 'store']);
    Route::get('/mortgages/{mortgageRequest}', [MortgageController::class, 'show']);

    // Payments
    Route::get('/mortgages/{mortgageRequest}/payment-breakdown', [PaymentController::class, 'breakdown']);
    Route::post('/mortgages/{mortgageRequest}/pay', [PaymentController::class, 'pay']);

    // Installments
    Route::get('/mortgages/{mortgageRequest}/installments', [InstallmentController::class, 'index']);
    Route::get('/installments/{installment}', [InstallmentController::class, 'show']);
});

// Public API endpoints — Houses
Route::get('/houses', [HouseController::class, 'index']);
Route::get('/houses/{slug}', [HouseController::class, 'show']);
Route::get('/houses/{slug}/interests', [HouseController::class, 'interests']);

// Public API endpoints — Master Data
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{slug}', [CityController::class, 'show']);
Route::get('/banks', [BankController::class, 'index']);
Route::get('/banks/{id}', [BankController::class, 'show']);
Route::get('/facilities', [FacilityController::class, 'index']);

// Public mortgage calculator
Route::post('/mortgages/calculate', [MortgageController::class, 'calculate']);

// Webhook endpoints (no auth — called by Midtrans)
Route::post('/webhook/midtrans', [DashboardController::class, 'payment_midtrans_notification']);
