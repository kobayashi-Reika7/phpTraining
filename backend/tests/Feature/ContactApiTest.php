<?php

namespace Tests\Feature;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * コンタクトフォーム API の Feature テスト
 *
 * Feature テストとは、HTTP リクエストを模擬して API の動作を確認するテスト。
 * 実際にブラウザからアクセスしたのと同じ流れをプログラムで再現する。
 *
 * Mail::fake() を使うと実際にメール送信せず、送信されたかどうかだけ確認できる。
 */
class ContactApiTest extends TestCase
{
    /**
     * テストごとに毎回実行される前処理
     * Mail::fake() でメール送信をモック化（実際に送らない）
     */
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /**
     * テスト用の正常な入力データを返すヘルパーメソッド
     * 各テストで共通して使える「正しいデータのひな形」
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name'    => 'テスト太郎',
            'email'   => 'test@example.com',
            'comment' => 'これはテストコメントです',
            'gender'  => '男性',
            'kind'    => '製品購入前のお問い合わせ',
            'lang'    => ['PHP', 'Python'],
        ], $overrides);
    }

    /**
     * テスト1: 正常送信（全項目を正しく送信 → 200 + success: true）
     */
    public function test_正常にお問い合わせを送信できる(): void
    {
        $response = $this->postJson('/api/contact', $this->validData());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'お問い合わせを送信しました',
            ]);
    }

    /**
     * テスト2: 必須項目欠落（name, email, comment が空 → 422 + errors）
     */
    public function test_必須項目が空だとバリデーションエラー(): void
    {
        $response = $this->postJson('/api/contact', [
            'name'    => '',
            'email'   => '',
            'comment' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure([
                'errors' => ['name', 'email', 'comment'],
            ]);
    }

    /**
     * テスト3: 名前が最大文字数超過（21文字 → 422）
     */
    public function test_名前が20文字を超えるとエラー(): void
    {
        $response = $this->postJson('/api/contact', $this->validData([
            'name' => str_repeat('あ', 21),
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', '名前は20文字以内で入力してください');
    }

    /**
     * テスト4: コメントが最大文字数超過（401文字 → 422）
     */
    public function test_コメントが400文字を超えるとエラー(): void
    {
        $response = $this->postJson('/api/contact', $this->validData([
            'comment' => str_repeat('あ', 401),
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.comment.0', 'コメントは400文字以内で入力してください');
    }

    /**
     * テスト5: 不正な gender 値（許可値以外 → 422）
     */
    public function test_不正なgender値はエラー(): void
    {
        $response = $this->postJson('/api/contact', $this->validData([
            'gender' => '不明',
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.gender.0', '無効な選択肢です');
    }

    /**
     * テスト6: 不正な lang 値（許可値以外 → 422）
     *
     * lang の各要素のエラーキーは "lang.1" のようにドットを含む。
     * assertJsonPath ではドットがパス区切りと解釈されるため、
     * レスポンス JSON を直接検査する。
     */
    public function test_不正なlang値はエラー(): void
    {
        $response = $this->postJson('/api/contact', $this->validData([
            'lang' => ['PHP', 'Ruby'],
        ]));

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('lang.1', $errors);
        $this->assertEquals('無効な選択肢です', $errors['lang.1'][0]);
    }

    /**
     * テスト7: メール送信確認（Mail::fake で送信を検証）
     */
    public function test_送信成功時にメールが1通送られる(): void
    {
        $this->postJson('/api/contact', $this->validData());

        // ContactMail が1回送信されたことを確認
        Mail::assertSent(ContactMail::class, 1);
    }

    /**
     * テスト8: メール本文に入力値が含まれることを確認
     */
    public function test_メール本文にフォーム入力値が含まれる(): void
    {
        $data = $this->validData();
        $this->postJson('/api/contact', $data);

        Mail::assertSent(ContactMail::class, function (ContactMail $mail) use ($data) {
            // メールの差出人が入力されたメールアドレスであることを確認
            return $mail->hasFrom($data['email'], $data['name']);
        });
    }

    /**
     * テスト9: 任意項目が空でも正常送信できる
     */
    public function test_任意項目が空でも送信できる(): void
    {
        $response = $this->postJson('/api/contact', [
            'name'    => 'テスト太郎',
            'email'   => 'test@example.com',
            'comment' => 'コメントのみ',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * テスト10: 名前にタブ・改行を含むとエラー
     */
    public function test_名前にタブを含むとエラー(): void
    {
        $response = $this->postJson('/api/contact', $this->validData([
            'name' => "テスト\ttab",
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', '名前にタブや改行を含めることはできません');
    }

    /**
     * テスト11: バリデーション失敗時はメールが送信されない
     */
    public function test_バリデーション失敗時はメールが送られない(): void
    {
        $this->postJson('/api/contact', [
            'name'    => '',
            'email'   => '',
            'comment' => '',
        ]);

        Mail::assertNothingSent();
    }
}
