<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 認証 API
|--------------------------------------------------------------------------
| POST /api/register  - ユーザー登録（トークン発行）
| POST /api/login     - ログイン（トークン発行）
| POST /api/logout    - ログアウト（トークン無効化）※要認証
| GET  /api/user      - ログインユーザー情報取得 ※要認証
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// ヘルスチェック
Route::get('/health', fn () => response()->json(['status' => 'ok']));
