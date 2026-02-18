<?php

/**
 * API ルーティング定義
 *
 * ここで URL と Controller のメソッドを紐づける。
 * 全てのルートに自動的に /api プレフィックスが付く。
 * 例: Route::get('/categories', ...) → GET /api/categories
 */

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DemoController;
use Illuminate\Support\Facades\Route;

// カテゴリ一覧: GET /api/categories
Route::get('/categories', [CategoryController::class, 'index']);

// カテゴリ内デモ一覧: GET /api/categories/{categoryId}/demos
Route::get('/categories/{categoryId}/demos', [CategoryController::class, 'demos']);

// デモ詳細: GET /api/demos/{filename}
Route::get('/demos/{filename}', [DemoController::class, 'show'])
    ->where('filename', '.*');  // スラッシュを含むファイル名（sym/new/hoge.php 等）に対応

// デモ実行: POST /api/demos/{filename}/run
Route::post('/demos/{filename}/run', [DemoController::class, 'run'])
    ->where('filename', '.*');
