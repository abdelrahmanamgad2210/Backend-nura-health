<?php

use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ClinicianCaseController;
use App\Http\Controllers\Api\ClinicianDecisionController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user']);

// Public: browsing the catalogue and building a cart never requires an
// account — only checkout (which creates a real Order tied to a user) does.
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']);
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/assessments', [AssessmentController::class, 'store']);
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show']);
    Route::patch('/assessments/{assessment}', [AssessmentController::class, 'update']);
    Route::post('/assessments/{assessment}/complete', [AssessmentController::class, 'complete']);

    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/items/{orderItem}/accept-plan', [OrderController::class, 'acceptPlan']);

    Route::get('/clinician/cases', [ClinicianCaseController::class, 'index']);
    Route::get('/clinician/cases/{case}', [ClinicianCaseController::class, 'show']);
    Route::post('/clinician/cases/{case}/decisions', [ClinicianDecisionController::class, 'store']);

    Route::get('/pharmacy/queue', [PharmacyController::class, 'index']);
    Route::get('/pharmacy/prescriptions/{prescription}', [PharmacyController::class, 'show']);
    Route::patch('/pharmacy/prescriptions/{prescription}/checklist', [PharmacyController::class, 'updateChecklist']);
    Route::post('/pharmacy/prescriptions/{prescription}/accept', [PharmacyController::class, 'accept']);
});
