<?php

/**
 * API ルーティング
 *
 * すべてのルートに /api プレフィックスが自動付与される。
 * 例: Route::post('/contact', ...) → POST /api/contact
 */

use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

Route::post('/contact', [ContactController::class, 'store']);
