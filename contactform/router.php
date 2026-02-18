<?php
/**
 * PHP ビルトインサーバー用ルーター
 *
 * .htaccess の RewriteRule を代替する。
 * 静的ファイルはそのまま返し、それ以外は index.php にルーティングする。
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

$_SERVER['PATH_INFO'] = $path;
require __DIR__ . '/public/index.php';
