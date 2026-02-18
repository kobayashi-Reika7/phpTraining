<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DemoService;
use Illuminate\Http\JsonResponse;

/**
 * デモファイルの詳細取得・実行を処理するコントローラ
 *
 * show: ファイルのソースコードとメタ情報を返す
 * run: ファイルを実際に PHP で実行し、stdout/stderr を返す
 */
class DemoController extends Controller
{
    public function __construct(
        private readonly DemoService $demoService
    ) {}

    /**
     * GET /api/demos/{filename}
     * デモ詳細（コード含む）を返す
     */
    public function show(string $filename): JsonResponse
    {
        $demo = $this->demoService->getDemo($filename);

        if ($demo === null) {
            return response()->json([
                'error' => 'デモファイルが見つかりません',
                'filename' => $filename,
            ], 404);
        }

        return response()->json(['data' => $demo]);
    }

    /**
     * POST /api/demos/{filename}/run
     * デモを実行して結果を返す
     *
     * セキュリティ上、ホワイトリストに含まれるファイルのみ実行可能。
     * パストラバーサル（../../etc/passwd 等）も DemoService 側で拒否する。
     */
    public function run(string $filename): JsonResponse
    {
        $result = $this->demoService->runDemo($filename);

        if (isset($result['error'])) {
            return response()->json($result, 403);
        }

        return response()->json(['data' => $result]);
    }
}
