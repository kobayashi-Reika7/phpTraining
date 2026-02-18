<?php

/**
 * API ルーティング
 *
 * すべてのルートに /api プレフィックスが自動付与される。
 * 例: Route::post('/contact', ...) → POST /api/contact
 */

use Illuminate\Support\Facades\Route;

Route::post('/contact', function () {
    // Phase 2 で ContactController に置き換え
    return response()->json(['status' => 'ok', 'message' => 'API is ready']);
});
