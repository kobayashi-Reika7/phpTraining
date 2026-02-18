<?php

/**
 * API ルーティング
 *
 * すべてのルートに /api プレフィックスが自動付与される。
 * 例: Route::post('/contact', ...) → POST /api/contact
 *
 * throttle:5,1 = 1分間に5回までリクエストを許可（スパム・DoS 対策）
 * 超過時は 429 Too Many Requests が返される。
 */

use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
});
