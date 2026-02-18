# 基本設計書：コンタクトフォーム（React + Laravel 移行）

## 1. システム構成

### 1.1 全体構成図

```
[ブラウザ]
    │
    │  http://localhost:5173  （開発時）
    ▼
[Vite 開発サーバー]  ←─ React 19 + TypeScript
    │
    │  /api/* → プロキシ → http://localhost:8000
    ▼
[Laravel 12 API サーバー]
    │
    │  MAIL_MAILER=log （開発時はログ出力）
    ▼
[メール送信]
```

### 1.2 通信方式
- フロントエンド → バックエンド：REST API（JSON）
- Vite のプロキシで CORS を回避（開発時）
- CSRF 対策：Laravel Sanctum は不使用、ステートレス API として設計

---

## 2. ディレクトリ構成

### 2.1 プロジェクト全体

```
phpTraining/
├── backend/                    ← Laravel 12（新規作成）
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       └── ContactController.php
│   │   │   └── Requests/
│   │   │       └── ContactRequest.php
│   │   ├── Mail/
│   │   │   └── ContactMail.php
│   │   └── Services/
│   │       └── ContactService.php
│   ├── config/
│   │   └── contact.php
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       └── Feature/
│           └── ContactApiTest.php
│
├── frontend/                   ← React 19 + TypeScript（新規作成）
│   └── src/
│       ├── types/
│       │   └── index.ts
│       ├── hooks/
│       │   └── useContactForm.ts
│       ├── components/
│       │   ├── FormField.tsx
│       │   └── FormLayout.tsx
│       ├── pages/
│       │   ├── InputPage.tsx
│       │   ├── ConfirmPage.tsx
│       │   ├── CompletePage.tsx
│       │   └── ErrorPage.tsx
│       ├── utils/
│       │   └── validation.ts
│       ├── App.tsx
│       └── App.css
│
├── contactform/                ← 既存 FuelPHP（参照用）
├── sample/                     ← 前回の練習成果
├── docs/                       ← ドキュメント
│   ├── requirements.md
│   └── basic-design.md
└── ai_query_logs/              ← 指示ログ
```

---

## 3. バックエンド設計（Laravel 12）

### 3.1 API エンドポイント

| メソッド | URL | 処理 | Controller メソッド |
|---------|-----|------|-------------------|
| POST | `/api/contact` | フォーム送信 | `ContactController@store` |

### 3.2 ContactRequest（バリデーション）

**なぜ FormRequest を使うのか：**
Laravel の FormRequest はバリデーションルールをコントローラから分離できる。
コントローラが肥大化せず、ルールの再利用もしやすい。

```php
// app/Http/Requests/ContactRequest.php
class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'max:20', 'regex:/\A[^\r\n\t]*\z/u'],
            'email'   => ['required', 'email:rfc'],
            'comment' => ['required', 'max:400'],
            'gender'  => ['nullable', 'in:男性,女性'],
            'kind'    => ['nullable', 'in:,製品購入前のお問い合わせ,製品購入後のお問い合わせ,その他'],
            'lang'    => ['nullable', 'array'],
            'lang.*'  => ['in:PHP,Perl,Python'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => '名前は必須です',
            'name.max'         => '名前は20文字以内で入力してください',
            'name.regex'       => '名前にタブや改行を含めることはできません',
            'email.required'   => 'メールアドレスは必須です',
            'email.email'      => '有効なメールアドレスを入力してください',
            'comment.required' => 'コメントは必須です',
            'comment.max'      => 'コメントは400文字以内で入力してください',
            'gender.in'        => '無効な選択肢です',
            'kind.in'          => '無効な選択肢です',
            'lang.array'       => '無効な形式です',
            'lang.*.in'        => '無効な選択肢です',
        ];
    }
}
```

### 3.3 ContactController

```php
// app/Http/Controllers/Api/ContactController.php
class ContactController extends Controller
{
    public function store(ContactRequest $request, ContactService $service): JsonResponse
    {
        // ContactRequest が自動的にバリデーションを実行
        // バリデーション失敗時は 422 レスポンスが自動返却される
        $service->send($request->validated(), $request->ip(), $request->userAgent());

        return response()->json([
            'success' => true,
            'message' => 'お問い合わせを送信しました',
        ]);
    }
}
```

### 3.4 ContactService

**なぜ Service 層を作るのか：**
コントローラはリクエスト/レスポンスの橋渡し役に徹し、
ビジネスロジック（メール組み立て・送信）は Service に分離する。
テストやメンテナンスがしやすくなる。

```php
// app/Services/ContactService.php
class ContactService
{
    public function send(array $data, string $ip, ?string $userAgent): void
    {
        Mail::to(config('contact.admin_email'))
            ->send(new ContactMail($data, $ip, $userAgent ?? ''));
    }
}
```

### 3.5 ContactMail（Mailable）

**なぜ Mailable を使うのか：**
Laravel の Mailable クラスはメールのテンプレート化、テスト、ログ出力を統一的に扱える。
`MAIL_MAILER=log` に設定するだけで、実際に送信せずログに出力できる。

```php
// app/Mail/ContactMail.php
class ContactMail extends Mailable
{
    public function __construct(
        private array $data,
        private string $ip,
        private string $userAgent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->data['email'], $this->data['name']),
            subject: config('contact.mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact', with: [
            'data'      => $this->data,
            'ip'        => $this->ip,
            'userAgent' => $this->userAgent,
        ]);
    }
}
```

### 3.6 設定ファイル

```php
// config/contact.php
return [
    'admin_name'   => env('CONTACT_ADMIN_NAME', '管理者'),
    'admin_email'  => env('CONTACT_ADMIN_EMAIL', 'admin@example.jp'),
    'mail_subject' => env('CONTACT_MAIL_SUBJECT', 'コンタクトフォーム'),
];
```

### 3.7 メールテンプレート

```
// resources/views/emails/contact.blade.php
====================
名前: {{ $data['name'] }}
メールアドレス: {{ $data['email'] }}
IPアドレス: {{ $ip }}
ブラウザ: {{ $userAgent }}
====================
コメント:
{{ $data['comment'] }}

性別: {{ $data['gender'] ?? '' }}
問い合わせの種類: {{ $data['kind'] ?? '' }}
使用プログラミング言語: {{ implode(' ', $data['lang'] ?? []) }}
====================
```

### 3.8 エラーハンドリング

| 状況 | HTTP ステータス | レスポンス |
|------|---------------|-----------|
| バリデーションエラー | 422 | `{ success: false, message: "...", errors: {...} }` |
| メール送信失敗 | 500 | `{ success: false, message: "メールの送信に失敗しました" }` |
| 送信成功 | 200 | `{ success: true, message: "お問い合わせを送信しました" }` |

---

## 4. フロントエンド設計（React 19）

### 4.1 画面遷移（SPA 内ステップ管理）

**なぜ React Router を使わないか：**
コンタクトフォームは1つの流れ（入力→確認→完了）なので、
ルーティングではなく `useState` でステップを管理する方がシンプル。
URL を変えないことで、ブラウザの「戻る」で入力途中のデータが消えるのを防ぐ。

```
step: "input" → "confirm" → "complete" | "error"
```

### 4.2 型定義

```typescript
// types/index.ts

/** フォームの入力データ */
interface ContactFormData {
  name: string;
  email: string;
  comment: string;
  gender: string;
  kind: string;
  lang: string[];
}

/** API レスポンス（成功） */
interface ApiSuccessResponse {
  success: true;
  message: string;
}

/** API レスポンス（エラー） */
interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

/** バリデーションエラーの型 */
type ValidationErrors = Partial<Record<keyof ContactFormData, string>>;
```

### 4.3 コンポーネント設計

```
App
└── FormLayout              ← 共通レイアウト（ヘッダー・コンテナ）
    ├── InputPage           ← ステップ "input"
    │   └── FormField ×6   ← 各入力項目の共通コンポーネント
    ├── ConfirmPage         ← ステップ "confirm"
    ├── CompletePage        ← ステップ "complete"
    └── ErrorPage           ← ステップ "error"
```

### 4.4 カスタムフック：useContactForm

**なぜカスタムフックを使うのか：**
フォームの状態管理（値、エラー、ステップ、送信処理）を1つのフックにまとめる。
各ページコンポーネントはこのフックの返り値だけを使えばよく、ロジックが散在しない。

```typescript
// hooks/useContactForm.ts
function useContactForm() {
  return {
    formData,           // 現在のフォーム値
    errors,             // バリデーションエラー
    step,               // 現在のステップ
    serverError,        // サーバーエラーメッセージ
    submitting,         // 送信中フラグ
    updateField,        // フィールド値を更新
    validateAndConfirm, // バリデーション → 確認画面へ
    goBackToInput,      // 入力画面に戻る
    submit,             // API 送信
    reset,              // 最初に戻る
  };
}
```

### 4.5 バリデーション（フロント側）

```typescript
// utils/validation.ts
function validateContactForm(data: ContactFormData): ValidationErrors {
  // name: 必須、最大20文字、タブ・改行禁止
  // email: 必須、メール形式
  // comment: 必須、最大400文字
  // gender: 許可値チェック
  // kind: 許可値チェック
  // lang: 許可値チェック
}
```

### 4.6 各ページの役割

| ページ | Props | 表示内容 |
|--------|-------|---------|
| InputPage | formData, errors, updateField, validateAndConfirm | フォーム6項目 + 確認ボタン |
| ConfirmPage | formData, goBackToInput, submit, submitting | 入力内容一覧 + 修正/送信ボタン |
| CompletePage | reset | 送信完了メッセージ + 最初に戻るリンク |
| ErrorPage | serverError, goBackToInput | エラーメッセージ + 戻るボタン |

---

## 5. セキュリティ設計

| 対策 | 実装方法 |
|------|---------|
| XSS | React の自動エスケープ（JSX）+ Laravel の Blade エスケープ |
| CSRF | ステートレス API のため不要（Cookie ベースのセッションを使わない） |
| バリデーション | フロント（UX）+ バック（セキュリティ）の二重チェック |
| メールヘッダインジェクション | Laravel の Mailable が自動でヘッダをサニタイズ |
| 入力フィルタ | ContactRequest の regex ルールでタブ・改行を排除 |

**なぜ CSRF トークンが不要か：**
既存の FuelPHP 版はセッションベース（サーバーが HTML を生成）だったので CSRF が必要だった。
新システムは API サーバー（JSON を返すだけ）なので、Cookie ベースの認証を使わない限り CSRF 攻撃のリスクがない。

---

## 6. テスト設計

### 6.1 バックエンド（Feature テスト）

| テスト | 内容 |
|--------|------|
| 正常送信 | 全項目を正しく送信 → 200 + success: true |
| 必須項目欠落 | name, email, comment を空で送信 → 422 + errors |
| 最大文字数超過 | name 21文字、comment 401文字 → 422 |
| 不正な gender | 許可値以外 → 422 |
| 不正な lang | 許可値以外 → 422 |
| メール送信確認 | Mail::fake() で送信を確認 |
| メール本文確認 | 本文に入力値が含まれることを確認 |

---

## 7. 環境構築手順

### 7.1 バックエンド

```bash
# Laravel プロジェクト作成（sample/backend をコピーするのではなく新規作成）
cd c:\Users\rei\phpTraining
C:\xampp\php\php.exe C:\xampp\php\composer.phar create-project laravel/laravel backend

# .env のメール設定を変更
MAIL_MAILER=log
CONTACT_ADMIN_NAME=管理者
CONTACT_ADMIN_EMAIL=admin@example.jp
CONTACT_MAIL_SUBJECT=コンタクトフォーム
```

### 7.2 フロントエンド

```bash
npm create vite@latest frontend -- --template react-ts

# vite.config.ts にプロキシ設定
# /api → http://localhost:8000
```

---

## 8. コーディング規約

| 対象 | ルール | 例 |
|------|--------|-----|
| PHP ファイル名 | PascalCase | `ContactController.php` |
| PHP クラス名 | PascalCase | `ContactService` |
| PHP メソッド名 | camelCase | `store()`, `send()` |
| TS ファイル名 | camelCase | `useContactForm.ts` |
| TSX ファイル名 | PascalCase | `InputPage.tsx` |
| TS 変数名 | camelCase | `formData`, `validateAndConfirm` |
| TS 型名 | PascalCase | `ContactFormData` |
| CSS クラス名 | kebab-case | `form-field`, `input-error` |

---

_作成日: 2026-02-18_
