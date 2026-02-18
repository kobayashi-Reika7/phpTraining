<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * デモファイルの取得・実行を担当するサービスクラス
 *
 * Controller からビジネスロジックを分離するために作成。
 * demos.json からメタデータを読み取り、PHPファイルの取得・実行を行う。
 * Controller はリクエストの受付とレスポンスの整形だけに集中できる。
 */
class DemoService
{
    /** メタデータのキャッシュ（同一リクエスト内で何度も読まないようにする） */
    private ?array $metadata = null;

    /**
     * demos.json を読み込んでメタデータを返す
     *
     * 一度読み込んだらプロパティにキャッシュして、
     * 同じリクエスト内で繰り返しファイルを読まないようにしている。
     */
    private function loadMetadata(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        $path = config('demo.metadata_path');

        if (!file_exists($path)) {
            throw new RuntimeException("メタデータファイルが見つかりません: {$path}");
        }

        $json = file_get_contents($path);
        $this->metadata = json_decode($json, true);

        return $this->metadata;
    }

    /**
     * 全カテゴリを取得する
     *
     * @return array カテゴリの配列（各カテゴリに demo_count を付与）
     */
    public function getCategories(): array
    {
        $metadata = $this->loadMetadata();
        $categories = [];

        foreach ($metadata['categories'] as $category) {
            $categories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['description'],
                'demo_count' => count($category['demos']),
            ];
        }

        return $categories;
    }

    /**
     * 指定カテゴリのデモ一覧を取得する
     *
     * @param string $categoryId カテゴリID（例: "array"）
     * @return array|null カテゴリ情報 + デモ一覧。存在しなければ null
     */
    public function getDemosByCategory(string $categoryId): ?array
    {
        $metadata = $this->loadMetadata();

        foreach ($metadata['categories'] as $category) {
            if ($category['id'] === $categoryId) {
                $demos = array_map(function ($demo) {
                    return [
                        'filename' => $demo['filename'],
                        'title' => $demo['title'],
                        'theme' => $demo['theme'],
                        'runnable' => $demo['runnable'],
                    ];
                }, $category['demos']);

                return [
                    'category' => [
                        'id' => $category['id'],
                        'name' => $category['name'],
                    ],
                    'demos' => $demos,
                ];
            }
        }

        return null;
    }

    /**
     * 指定ファイルのデモ詳細（コード含む）を取得する
     *
     * @param string $filename PHPファイル名（例: "array_at.php"）
     * @return array|null デモ詳細。存在しなければ null
     */
    public function getDemo(string $filename): ?array
    {
        if (!$this->isAllowedFile($filename)) {
            return null;
        }

        $metadata = $this->loadMetadata();

        foreach ($metadata['categories'] as $category) {
            foreach ($category['demos'] as $demo) {
                if ($demo['filename'] === $filename) {
                    $filePath = config('demo.source_dir') . DIRECTORY_SEPARATOR . $filename;
                    $code = file_exists($filePath) ? file_get_contents($filePath) : '';

                    return [
                        'filename' => $demo['filename'],
                        'title' => $demo['title'],
                        'theme' => $demo['theme'],
                        'description' => $demo['description'],
                        'runnable' => $demo['runnable'],
                        'category' => [
                            'id' => $category['id'],
                            'name' => $category['name'],
                        ],
                        'code' => $code,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * 指定ファイルを PHP で実行し、結果を返す
     *
     * セキュリティ対策として、ホワイトリスト（demos.json に登録済み）に
     * 含まれるファイルのみ実行を許可する。
     *
     * @param string $filename PHPファイル名
     * @return array 実行結果（stdout, stderr, exit_code）
     */
    public function runDemo(string $filename): array
    {
        if (!$this->isAllowedFile($filename)) {
            return [
                'error' => 'このファイルは実行できません',
                'filename' => $filename,
            ];
        }

        $demo = $this->findDemo($filename);
        if ($demo && !$demo['runnable']) {
            return [
                'error' => 'このファイルは実行不可としてマークされています',
                'filename' => $filename,
            ];
        }

        $phpPath = config('demo.php_path');
        $sourceDir = config('demo.source_dir');
        $filePath = $sourceDir . DIRECTORY_SEPARATOR . $filename;
        $timeout = config('demo.timeout');

        if (!file_exists($filePath)) {
            return [
                'error' => 'ファイルが見つかりません',
                'filename' => $filename,
            ];
        }

        // Laravel の Process ファサードでPHPを実行する
        // タイムアウトで無限ループ等を防止
        $result = Process::timeout($timeout)
            ->path($sourceDir)
            ->command([$phpPath, $filePath])
            ->run();

        return [
            'filename' => $filename,
            'stdout' => $result->output(),
            'stderr' => $result->errorOutput(),
            'exit_code' => $result->exitCode(),
            'executed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * ファイル名がホワイトリスト（demos.json に登録済み）に含まれるか検証する
     *
     * パストラバーサル攻撃（../etc/passwd 等）を防ぐために、
     * メタデータに登録されたファイル名のみ許可する。
     */
    private function isAllowedFile(string $filename): bool
    {
        // パストラバーサルを含むファイル名を拒否
        if (str_contains($filename, '..')) {
            return false;
        }

        $metadata = $this->loadMetadata();

        foreach ($metadata['categories'] as $category) {
            foreach ($category['demos'] as $demo) {
                if ($demo['filename'] === $filename) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ファイル名からデモのメタデータを検索する
     */
    private function findDemo(string $filename): ?array
    {
        $metadata = $this->loadMetadata();

        foreach ($metadata['categories'] as $category) {
            foreach ($category['demos'] as $demo) {
                if ($demo['filename'] === $filename) {
                    return $demo;
                }
            }
        }

        return null;
    }
}
