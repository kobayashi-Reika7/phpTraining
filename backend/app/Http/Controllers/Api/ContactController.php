<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * コンタクトフォーム API コントローラ
 *
 * コントローラの役割は「リクエストを受け取り、レスポンスを返す」こと。
 * バリデーションは ContactRequest に、ビジネスロジックは ContactService に委譲する。
 */
class ContactController extends Controller
{
    /**
     * お問い合わせフォームの送信処理
     *
     * 処理の流れ：
     * 1. ContactRequest がバリデーションを自動実行（失敗 → 422 レスポンス）
     * 2. バリデーション通過後、ContactService でメール送信
     * 3. 成功レスポンスを返す
     */
    public function store(ContactRequest $request, ContactService $service): JsonResponse
    {
        try {
            $service->send(
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'success' => true,
                'message' => 'お問い合わせを送信しました',
            ]);
        } catch (\Exception $e) {
            // エラーの詳細をログに記録（デバッグに必要な情報を含める）
            Log::error('Contact form mail send failed', [
                'error'   => $e->getMessage(),
                'ip'      => $request->ip(),
                'email'   => $request->input('email'),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メールの送信に失敗しました',
            ], 500);
        }
    }
}
