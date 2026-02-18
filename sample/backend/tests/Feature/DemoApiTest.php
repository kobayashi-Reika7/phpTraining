<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * デモ API のテスト
 *
 * デモの詳細取得と実行のテスト。
 * 実行テストでは実際にPHPファイルを実行するため、
 * PHP実行環境（XAMPP）が必要になる。
 */
class DemoApiTest extends TestCase
{
    /** デモ詳細を取得できることを確認 */
    public function test_デモ詳細を取得できる(): void
    {
        $response = $this->getJson('/api/demos/array_at.php');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'filename',
                    'title',
                    'theme',
                    'description',
                    'runnable',
                    'category' => ['id', 'name'],
                    'code',
                ],
            ]);

        $response->assertJsonFragment([
            'filename' => 'array_at.php',
            'title' => '存在しないキーへのアクセス',
        ]);
    }

    /** ソースコードが含まれていることを確認 */
    public function test_デモ詳細にコードが含まれる(): void
    {
        $response = $this->getJson('/api/demos/double_equal.php');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertStringContainsString('<?php', $data['code']);
    }

    /** 存在しないファイルで 404 が返ることを確認 */
    public function test_存在しないデモは404を返す(): void
    {
        $response = $this->getJson('/api/demos/nonexistent.php');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'error' => 'デモファイルが見つかりません',
            ]);
    }

    /** パストラバーサルが拒否されることを確認 */
    public function test_パストラバーサルは拒否される(): void
    {
        $response = $this->getJson('/api/demos/..%2F..%2Fetc%2Fpasswd');

        $response->assertStatus(404);
    }

    /** デモを実行して結果を取得できることを確認 */
    public function test_デモを実行できる(): void
    {
        $response = $this->postJson('/api/demos/dainyu.php/run');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'filename',
                    'stdout',
                    'stderr',
                    'exit_code',
                    'executed_at',
                ],
            ]);

        $response->assertJsonFragment([
            'filename' => 'dainyu.php',
            'exit_code' => 0,
        ]);
    }

    /** 実行不可（runnable=false）のファイルは拒否されることを確認 */
    public function test_実行不可ファイルは403を返す(): void
    {
        $response = $this->postJson('/api/demos/pdo_last_insert_id.php/run');

        $response->assertStatus(403);
    }

    /** パストラバーサルでの実行が拒否されることを確認 */
    public function test_パストラバーサルでの実行は拒否される(): void
    {
        $response = $this->postJson('/api/demos/..%2F..%2Fetc%2Fpasswd/run');

        $response->assertStatus(403);
    }
}
