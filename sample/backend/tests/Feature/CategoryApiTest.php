<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * カテゴリ API のテスト
 *
 * Feature テストは「実際にHTTPリクエストを送って、レスポンスを検証する」テスト。
 * 単体テスト（Unit）と違い、ルーティング・Controller・Service を通しで確認できる。
 */
class CategoryApiTest extends TestCase
{
    /** カテゴリ一覧が正常に取得できることを確認 */
    public function test_カテゴリ一覧を取得できる(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'demo_count'],
                ],
            ]);

        // 7カテゴリが存在すること
        $response->assertJsonCount(7, 'data');
    }

    /** 最初のカテゴリが「配列」であることを確認 */
    public function test_最初のカテゴリは配列である(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => 'array',
                'name' => '配列（Array）',
            ]);
    }

    /** 存在するカテゴリのデモ一覧を取得できることを確認 */
    public function test_カテゴリ内デモ一覧を取得できる(): void
    {
        $response = $this->getJson('/api/categories/array/demos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'category' => ['id', 'name'],
                    'demos' => [
                        '*' => ['filename', 'title', 'theme', 'runnable'],
                    ],
                ],
            ]);

        // 配列カテゴリには 13 デモがあること
        $response->assertJsonCount(13, 'data.demos');
    }

    /** 存在しないカテゴリで 404 が返ることを確認 */
    public function test_存在しないカテゴリは404を返す(): void
    {
        $response = $this->getJson('/api/categories/nonexistent/demos');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'error' => 'カテゴリが見つかりません',
            ]);
    }
}
