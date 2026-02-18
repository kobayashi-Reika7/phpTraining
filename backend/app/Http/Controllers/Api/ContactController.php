<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

/**
 * コンタクトフォーム API コントローラ
 *
 * コントローラの役割は「リクエストを受け取り、レスポンスを返す」こと。
 * バリデーションは ContactRequest に、ビジネスロジックは ContactService に委譲する。
 * こうすることでコントローラが肥大化（Fat Controller）するのを防ぐ。
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
     *
     * @param ContactRequest $request  バリデーション済みのリクエスト
     * @param ContactService $service  メール送信サービス（依存性注入）
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
            // メール送信に失敗した場合のエラーハンドリング
            return response()->json([
                'success' => false,
                'message' => 'メールの送信に失敗しました',
            ], 500);
        }
    }
}
