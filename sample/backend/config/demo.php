<?php

/**
 * デモ実行に関する設定ファイル
 *
 * PHP実行パスやソースファイルの場所など、
 * 環境に依存する値を .env から取得できるようにしている。
 * ハードコードを避けることで、別環境でも設定変更だけで動く。
 */
return [
    // PHP 実行ファイルのパス（環境変数 DEMO_PHP_PATH で上書き可能）
    'php_path' => env('DEMO_PHP_PATH', 'C:\\xampp\\php\\php.exe'),

    // 既存PHPファイルのディレクトリ（プロジェクトルートからの相対パス）
    'source_dir' => env('DEMO_SOURCE_DIR', base_path('../php-omoshiroi-code')),

    // カテゴリ・デモのメタデータ JSON のパス
    'metadata_path' => storage_path('app/demos.json'),

    // PHP実行時のタイムアウト（秒）。無限ループ等を防ぐ安全策
    'timeout' => 5,
];
