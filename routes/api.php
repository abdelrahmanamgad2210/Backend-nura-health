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

// The SPA and API live on entirely different domains (Vercel / Railway), so
// the frontend can never read the XSRF-TOKEN cookie via document.cookie —
// that only works when both share a parent domain. Hand the raw session
// token back in the response body instead, for use as an X-CSRF-TOKEN
// header. GET is exempt from CSRF verification, and StartSession (via
// statefulApi()) creates the session + queues its cookie on this request.
Route::get('/csrf-token', function (\Illuminate\Http\Request $request) {
    return response()->json(['token' => $request->session()->token()]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user']);

// Public: browsing the catalogue, building a cart, and answering the intake
// assessment never require an account — only checkout (which creates a real
// Order tied to a user) and submitting a completed assessment for clinical
// review (which creates a ClinicalCase tied to a real patient) do.
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']);
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);
Route::post('/assessments', [AssessmentController::class, 'store']);
Route::get('/assessments/{assessment}', [AssessmentController::class, 'show']);
Route::patch('/assessments/{assessment}', [AssessmentController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/assessments', [AssessmentController::class, 'index']);
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
