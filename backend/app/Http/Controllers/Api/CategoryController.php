<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DemoService;
use Illuminate\Http\JsonResponse;

/**
 * カテゴリに関する API を処理するコントローラ
 *
 * Controller の役割は「リクエストを受けて、Service を呼んで、レスポンスを返す」だけ。
 * ビジネスロジック（データの取得・加工）は DemoService に任せている。
 * これにより Controller が薄く保たれ、テストもしやすくなる。
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly DemoService $demoService
    ) {}

    /**
     * GET /api/categories
     * カテゴリ一覧を返す
     */
    public function index(): JsonResponse
    {
        $categories = $this->demoService->getCategories();

        return response()->json(['data' => $categories]);
    }

    /**
     * GET /api/categories/{categoryId}/demos
     * 指定カテゴリのデモ一覧を返す
     */
    public function demos(string $categoryId): JsonResponse
    {
        $result = $this->demoService->getDemosByCategory($categoryId);

        if ($result === null) {
            return response()->json([
                'error' => 'カテゴリが見つかりません',
                'category_id' => $categoryId,
            ], 404);
        }

        return response()->json(['data' => $result]);
    }
}
