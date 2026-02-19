<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SlotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 認証 API
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| 公開 API（認証不要）
|--------------------------------------------------------------------------
*/
Route::get('/departments', [DoctorController::class, 'departments']);
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/department', [DoctorController::class, 'byDepartment']);

/*
|--------------------------------------------------------------------------
| 認証必須 API
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Slots（空き枠照会）
    Route::get('/slots', [SlotController::class, 'index']);

    // Reservations（予約 CRUD）
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);
});

// ヘルスチェック
Route::get('/health', fn () => response()->json(['status' => 'ok']));
