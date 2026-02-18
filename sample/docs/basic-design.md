# 基本設計書：PHP Omoshiroi Viewer

> 対応する要件定義書: [docs/requirements.md](./requirements.md)

---

## 1. システム構成図

### 1.1 全体アーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                     開発者の PC（localhost）                    │
│                                                              │
│  ┌──────────────────┐       ┌──────────────────────────┐    │
│  │  React (Vite)     │ HTTP  │  Laravel 12 (API)         │    │
│  │  localhost:5173   │──────▶│  localhost:8000            │    │
│  │                   │ JSON  │                            │    │
│  │  - カテゴリ一覧    │◀──────│  /api/categories           │    │
│  │  - デモ一覧       │       │  /api/categories/:id/demos │    │
│  │  - コード表示     │       │  /api/demos/:filename      │    │
│  │  - 実行結果表示   │       │  /api/demos/:filename/run  │    │
│  └──────────────────┘       │                            │    │
│                              │  ┌──────────────────────┐ │    │
│                              │  │ Service 層            │ │    │
│                              │  │  DemoService          │ │    │
│                              │  │   ↓ ファイル読取      │ │    │
│                              │  │   ↓ PHP 実行          │ │    │
│                              │  └──────────────────────┘ │    │
│                              │          ↓                 │    │
│                              │  ┌──────────────────────┐ │    │
│                              │  │ データソース           │ │    │
│                              │  │  demos.json（メタ情報）│ │    │
│                              │  │  php-omoshiroi-code/  │ │    │
│                              │  │   └ *.php（実ファイル）│ │    │
│                              │  └──────────────────────┘ │    │
│                              └──────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

#### なぜこの構成か

- **React と Laravel を完全に分離**することで、フロントとバックを独立して開発・テストできる
- React 開発サーバー（Vite: ポート 5173）と Laravel 開発サーバー（artisan serve: ポート 8000）を別々に起動する
- API を介した通信なので、将来モバイルアプリ等にも対応可能

### 1.2 通信フロー

```
ブラウザ → React (localhost:5173)
            → fetch("/api/categories")
                → Laravel (localhost:8000)
                    → DemoService → demos.json を読取
                    ← JSON レスポンス
            ← 画面描画

ブラウザ → React (localhost:5173)
            → fetch("/api/demos/array_at.php/run", { method: "POST" })
                → Laravel (localhost:8000)
                    → DemoService → php.exe で array_at.php を実行
                    ← { stdout, stderr, exitCode }
            ← 実行結果を表示
```

---

## 2. ディレクトリ構成

### 2.1 プロジェクト全体

```
phpTraining/                         # ワークスペースルート
├── .cursorrules                     # Cursor Rules（既存）
├── ai_query_logs/                   # 指示ログ（既存）
│   └── 2026_02_18.md
├── docs/                            # ドキュメント
│   ├── requirements.md              # 要件定義書
│   └── basic-design.md              # 基本設計書（本ファイル）
├── php-omoshiroi-code/              # 既存PHPスクリプト集（読み取り専用・変更しない）
│   ├── *.php
│   └── ...
├── backend/                         # ★ Laravel 12 プロジェクト（新規作成）
│   └── （2.2 で詳述）
└── frontend/                        # ★ React 19 + Vite プロジェクト（新規作成）
    └── （2.3 で詳述）
```

#### なぜこの配置か

- `backend/` と `frontend/` を同じ階層に置くことで、1つのリポジトリで管理できる（モノレポ構成）
- `php-omoshiroi-code/` は既存ファイルとして温存し、`backend/` から参照する
- 実務では別リポジトリにすることも多いが、学習用途なので同一リポジトリが管理しやすい

### 2.2 backend/（Laravel 12）

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── CategoryController.php   # カテゴリ一覧 API
│   │           └── DemoController.php       # デモ詳細・実行 API
│   └── Services/
│       └── DemoService.php                  # ビジネスロジック（ファイル読取・実行）
├── config/
│   └── demo.php                             # デモ設定（PHPパス、ファイルパス等）
├── database/
│   └── （今回は未使用）
├── routes/
│   └── api.php                              # API ルーティング定義
├── storage/
│   └── app/
│       └── demos.json                       # カテゴリ・デモのメタデータ
├── tests/
│   └── Feature/
│       ├── CategoryApiTest.php              # カテゴリ API テスト
│       └── DemoApiTest.php                  # デモ API テスト
├── .env                                     # 環境変数
├── composer.json
└── artisan
```

#### なぜこの構造か（新人向け解説）

| ディレクトリ/ファイル | 役割 | なぜ必要か |
|---------------------|------|-----------|
| `Controllers/Api/` | HTTP リクエストを受け取り、レスポンスを返す | Laravel の MVC パターンの C（Controller） |
| `Services/` | ビジネスロジックを置く | Controller を薄く保ち、ロジックを再利用しやすくするため |
| `config/demo.php` | PHPパスやファイルパスなど環境依存の設定 | ハードコードを避け、環境ごとに変更できるようにする |
| `routes/api.php` | APIのURLとControllerを紐づける | どのURLでどの処理が動くか一目で分かる |
| `storage/app/demos.json` | デモのメタデータ | DBを使わずにデータを管理する（学習用の簡易構成） |
| `tests/Feature/` | APIの動作テスト | リファクタリング時に壊れていないことを確認する |

### 2.3 frontend/（React 19 + Vite）

```
frontend/
├── public/
│   └── favicon.ico
├── src/
│   ├── components/                  # 再利用可能な UI パーツ
│   │   ├── Layout.tsx               # 全体レイアウト（ヘッダー + サイドバー + メイン）
│   │   ├── Sidebar.tsx              # サイドバー（カテゴリ一覧）
│   │   ├── CodeBlock.tsx            # シンタックスハイライト付きコード表示
│   │   └── ExecutionResult.tsx      # 実行結果表示（stdout / stderr）
│   ├── pages/                       # 画面単位のコンポーネント
│   │   ├── TopPage.tsx              # S-01: トップページ
│   │   ├── CategoryPage.tsx         # S-02: カテゴリ詳細
│   │   └── DemoPage.tsx             # S-03: デモ詳細
│   ├── hooks/                       # カスタムフック
│   │   └── useApi.ts                # API 通信の共通処理
│   ├── types/                       # TypeScript 型定義
│   │   └── index.ts                 # Category, Demo 等の型
│   ├── App.tsx                      # ルーティング定義（React Router）
│   ├── main.tsx                     # エントリーポイント
│   └── index.css                    # グローバルスタイル
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts                   # Vite設定（API プロキシ含む）
└── eslint.config.js
```

#### なぜこの構造か（新人向け解説）

| ディレクトリ/ファイル | 役割 | なぜ必要か |
|---------------------|------|-----------|
| `components/` | 再利用可能な UI パーツ | 同じ見た目を複数画面で使い回せる |
| `pages/` | 画面単位のコンポーネント | URLと画面が1対1で対応し、分かりやすい |
| `hooks/` | ロジックの共通化 | API通信などの処理を1箇所にまとめる |
| `types/` | TypeScript の型定義 | データの形を明確にし、バグを防ぐ |
| `vite.config.ts` | Vite の設定 | API プロキシ（CORSの回避）を設定する |

---

## 3. API 詳細設計

### 3.1 A-01: カテゴリ一覧取得

```
GET /api/categories
```

**レスポンス（200 OK）:**

```json
{
  "data": [
    {
      "id": "array",
      "name": "配列（Array）",
      "description": "PHPの配列はArrayとHashの両方を兼ねており、独特な挙動がある",
      "demo_count": 13
    },
    {
      "id": "type-comparison",
      "name": "型比較・型変換",
      "description": "PHPの緩い型比較（==）や暗黙の型変換による予想外の挙動",
      "demo_count": 9
    },
    {
      "id": "function",
      "name": "関数",
      "description": "関数の引数、戻り値、スコープに関する挙動",
      "demo_count": 5
    },
    {
      "id": "class-object",
      "name": "クラス・オブジェクト",
      "description": "継承、マジックメソッド、プロパティに関する挙動",
      "demo_count": 5
    },
    {
      "id": "datetime",
      "name": "日時（DateTime）",
      "description": "PHPの日時処理における落とし穴",
      "demo_count": 2
    },
    {
      "id": "string-regex",
      "name": "文字列・正規表現",
      "description": "文字列操作と正規表現の注意点",
      "demo_count": 7
    },
    {
      "id": "other",
      "name": "その他",
      "description": "定数スコープ、exec、シンボリックリンクなど",
      "demo_count": 6
    }
  ]
}
```

### 3.2 A-02: カテゴリ内デモ一覧

```
GET /api/categories/{categoryId}/demos
```

**パスパラメータ:**

| パラメータ | 型 | 例 | 説明 |
|-----------|-----|-----|------|
| categoryId | string | `array` | カテゴリID |

**レスポンス（200 OK）:**

```json
{
  "data": [
    {
      "filename": "array_at.php",
      "title": "存在しないキーへのアクセス",
      "theme": "Warning + null 返却"
    },
    {
      "filename": "array_false.php",
      "title": "array_unique と false 値",
      "theme": "false 値の重複排除"
    }
  ],
  "category": {
    "id": "array",
    "name": "配列（Array）"
  }
}
```

**エラーレスポンス（404 Not Found）:**

```json
{
  "error": "カテゴリが見つかりません",
  "category_id": "invalid-id"
}
```

### 3.3 A-03: デモ詳細取得

```
GET /api/demos/{filename}
```

**パスパラメータ:**

| パラメータ | 型 | 例 | 説明 |
|-----------|-----|-----|------|
| filename | string | `array_at.php` | PHPファイル名 |

**レスポンス（200 OK）:**

```json
{
  "data": {
    "filename": "array_at.php",
    "title": "存在しないキーへのアクセス",
    "theme": "Warning + null 返却",
    "category": {
      "id": "array",
      "name": "配列（Array）"
    },
    "code": "<?php\n\n$a = [];\n\nif ($a['not'] !== null) {\n\tvar_dump('truedesu');\n} else {\n\tvar_dump('falsedesu');\n}\n",
    "description": "空の配列に存在しないキー 'not' でアクセスすると、Warning が出るが処理は止まらず、null が返る。そのため !== null の判定は false となり、else 側が実行される。"
  }
}
```

**エラーレスポンス（404 Not Found）:**

```json
{
  "error": "デモファイルが見つかりません",
  "filename": "not_exist.php"
}
```

### 3.4 A-04: デモ実行

```
POST /api/demos/{filename}/run
```

**パスパラメータ:**

| パラメータ | 型 | 例 | 説明 |
|-----------|-----|-----|------|
| filename | string | `array_at.php` | PHPファイル名 |

**レスポンス（200 OK）:**

```json
{
  "data": {
    "filename": "array_at.php",
    "stdout": "string(9) \"falsedesu\"\n",
    "stderr": "PHP Warning:  Undefined array key \"not\" in ...array_at.php on line 7\n",
    "exit_code": 0,
    "executed_at": "2026-02-18T11:30:00+09:00"
  }
}
```

**エラーレスポンス（403 Forbidden）:**

```json
{
  "error": "このファイルは実行できません",
  "filename": "../../etc/passwd"
}
```

#### セキュリティ制約

- `filename` はホワイトリスト（`demos.json` に登録済みのファイル）でチェックする
- パストラバーサル（`../`）を含むリクエストは拒否する
- 実行タイムアウト: 5秒

---

## 4. 画面設計

### 4.1 S-01: トップページ（`/`）

```
┌──────────────────────────────────────────────────────────┐
│  🐘 PHP Omoshiroi Viewer                                  │
├────────────┬─────────────────────────────────────────────┤
│            │                                              │
│  カテゴリ   │  PHP面白コード集                               │
│            │  ─────────────────                           │
│  ▶ 配列    │                                              │
│    (13)    │  PHPの面白い仕様をインタラクティブに             │
│            │  確認できるビューアです。                        │
│  型比較    │                                              │
│    (9)     │  左のカテゴリから見たいテーマを選んでください。    │
│            │                                              │
│  関数      │  ┌────────┐ ┌────────┐ ┌────────┐           │
│    (5)     │  │ 配列    │ │ 型比較  │ │ 関数   │           │
│            │  │ 13件    │ │ 9件    │ │ 5件    │           │
│  クラス    │  └────────┘ └────────┘ └────────┘           │
│    (5)     │  ┌────────┐ ┌────────┐ ┌────────┐           │
│            │  │ クラス  │ │ 日時   │ │ 文字列  │           │
│  日時      │  │ 5件    │ │ 2件    │ │ 7件    │           │
│    (2)     │  └────────┘ └────────┘ └────────┘           │
│            │  ┌────────┐                                  │
│  文字列    │  │ その他  │                                  │
│    (7)     │  │ 6件    │                                  │
│            │  └────────┘                                  │
│  その他    │                                              │
│    (6)     │                                              │
│            │                                              │
└────────────┴─────────────────────────────────────────────┘
```

**使用コンポーネント:** `Layout`, `Sidebar`, カテゴリカード（`TopPage` 内で描画）

### 4.2 S-02: カテゴリ詳細（`/category/:id`）

```
┌──────────────────────────────────────────────────────────┐
│  🐘 PHP Omoshiroi Viewer                                  │
├────────────┬─────────────────────────────────────────────┤
│            │                                              │
│  カテゴリ   │  配列（Array）                                │
│            │  ─────────────────                           │
│  ▶ 配列    │  PHPの配列はArrayとHashの両方を兼ねて          │
│  ★ (13)   │  おり、独特な挙動がある。                       │
│            │                                              │
│    型比較   │  ┌────────────────────────────────────────┐ │
│    (9)     │  │ 📄 array_at.php                         │ │
│            │  │    存在しないキーへのアクセス              │ │
│    関数    │  │    Warning + null 返却                   │ │
│    (5)     │  └────────────────────────────────────────┘ │
│            │  ┌────────────────────────────────────────┐ │
│    ...     │  │ 📄 array_false.php                      │ │
│            │  │    array_unique と false 値              │ │
│            │  │    false 値の重複排除                    │ │
│            │  └────────────────────────────────────────┘ │
│            │  ┌────────────────────────────────────────┐ │
│            │  │ 📄 array_in_array.php                   │ │
│            │  │    in_array の型変換                     │ │
│            │  │    strict モードの違い                   │ │
│            │  └────────────────────────────────────────┘ │
│            │  ...                                        │
└────────────┴─────────────────────────────────────────────┘
```

**使用コンポーネント:** `Layout`, `Sidebar`, デモカード一覧（`CategoryPage` 内で描画）

### 4.3 S-03: デモ詳細（`/demo/:filename`）

```
┌──────────────────────────────────────────────────────────┐
│  🐘 PHP Omoshiroi Viewer                                  │
├────────────┬─────────────────────────────────────────────┤
│            │                                              │
│  カテゴリ   │  ← 配列（Array）に戻る                       │
│            │                                              │
│  ▶ 配列    │  array_at.php                               │
│  ★ (13)   │  存在しないキーへのアクセス                     │
│            │  ─────────────────                           │
│    型比較   │                                              │
│    (9)     │  📄 ソースコード                              │
│            │  ┌──────────────────────────────────────┐   │
│    ...     │  │ <?php                                │   │
│            │  │                                      │   │
│            │  │ $a = [];                             │   │
│            │  │                                      │   │
│            │  │ if ($a['not'] !== null) {             │   │
│            │  │     var_dump('truedesu');             │   │
│            │  │ } else {                             │   │
│            │  │     var_dump('falsedesu');            │   │
│            │  │ }                                    │   │
│            │  └──────────────────────────────────────┘   │
│            │                                              │
│            │  [ ▶ 実行する ]                               │
│            │                                              │
│            │  📟 実行結果                                  │
│            │  ┌──────────────────────────────────────┐   │
│            │  │ ⚠ Warning: Undefined array key "not" │   │
│            │  │   in array_at.php on line 7          │   │
│            │  │                                      │   │
│            │  │ string(9) "falsedesu"                │   │
│            │  └──────────────────────────────────────┘   │
│            │                                              │
│            │  💡 解説（Phase 3 で追加予定）                  │
│            │                                              │
└────────────┴─────────────────────────────────────────────┘
```

**使用コンポーネント:** `Layout`, `Sidebar`, `CodeBlock`, `ExecutionResult`

---

## 5. コンポーネント設計（React）

### 5.1 コンポーネントツリー

```
App
├── Layout
│   ├── Header                    # アプリ名表示
│   ├── Sidebar                   # カテゴリ一覧（全画面で共通）
│   │   └── CategoryLink          # 各カテゴリへのリンク
│   └── Main (children)
│       ├── TopPage               # S-01
│       │   └── CategoryCard      # カテゴリカード
│       ├── CategoryPage          # S-02
│       │   └── DemoCard          # デモファイルカード
│       └── DemoPage              # S-03
│           ├── CodeBlock         # コード表示
│           └── ExecutionResult   # 実行結果表示
```

### 5.2 各コンポーネントの責務

| コンポーネント | 責務 | Props | 状態管理 |
|--------------|------|-------|---------|
| `App` | ルーティング定義（React Router） | なし | なし |
| `Layout` | ヘッダー + サイドバー + メイン領域の配置 | `children` | なし |
| `Sidebar` | カテゴリ一覧を表示、現在のカテゴリをハイライト | なし | `categories`（APIから取得） |
| `TopPage` | カテゴリカードを並べて表示 | なし | `categories`（APIから取得） |
| `CategoryPage` | 選択カテゴリのデモ一覧を表示 | なし | `demos`（APIから取得） |
| `DemoPage` | コード + 実行結果を表示 | なし | `demo`, `result`（APIから取得） |
| `CodeBlock` | シンタックスハイライト付きコード表示 | `code: string`, `language: string` | なし |
| `ExecutionResult` | stdout/stderr を色分け表示 | `stdout`, `stderr`, `exitCode` | なし |

### 5.3 カスタムフック

| フック名 | 役割 | 戻り値 |
|---------|------|--------|
| `useApi<T>(url)` | GET リクエストの共通処理 | `{ data, loading, error }` |

#### なぜカスタムフックを使うか

- API 通信の「ローディング中」「エラー」「データ取得完了」のパターンが全画面で共通
- 1箇所にまとめることで、同じコードを何度も書かなくて済む

---

## 6. バックエンド設計（Laravel）

### 6.1 レイヤー構成

```
HTTP Request
    ↓
routes/api.php          ... URL → Controller のマッピング
    ↓
Controller              ... リクエスト検証 → Service呼出 → レスポンス整形
    ↓
DemoService             ... ビジネスロジック（ファイル読取・PHP実行）
    ↓
demos.json / *.php      ... データソース
```

#### なぜ Service 層を置くか

- Controller にロジックを直接書くと、テストしにくく、再利用もできない
- Service に切り出しておけば、将来 DB に切り替えたいとき等も Controller を変更せずに済む

### 6.2 Controller 設計

#### CategoryController

```php
class CategoryController extends Controller
{
    // GET /api/categories → カテゴリ一覧を返す
    public function index(): JsonResponse

    // GET /api/categories/{categoryId}/demos → カテゴリ内デモ一覧を返す
    public function demos(string $categoryId): JsonResponse
}
```

#### DemoController

```php
class DemoController extends Controller
{
    // GET /api/demos/{filename} → デモ詳細（コード含む）を返す
    public function show(string $filename): JsonResponse

    // POST /api/demos/{filename}/run → デモを実行して結果を返す
    public function run(string $filename): JsonResponse
}
```

### 6.3 Service 設計

#### DemoService

```php
class DemoService
{
    // 全カテゴリを取得する
    public function getCategories(): array

    // 指定カテゴリのデモ一覧を取得する
    public function getDemosByCategory(string $categoryId): array

    // 指定ファイルのデモ詳細（コード含む）を取得する
    public function getDemo(string $filename): array

    // 指定ファイルを PHP で実行し、結果を返す
    public function runDemo(string $filename): array

    // ファイル名がホワイトリストに含まれるか検証する（セキュリティ）
    private function isAllowedFile(string $filename): bool
}
```

### 6.4 ルーティング定義

```php
// routes/api.php

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{categoryId}/demos', [CategoryController::class, 'demos']);
Route::get('/demos/{filename}', [DemoController::class, 'show']);
Route::post('/demos/{filename}/run', [DemoController::class, 'run']);
```

### 6.5 設定ファイル

```php
// config/demo.php
return [
    // PHP 実行パス（環境変数 or デフォルト値）
    'php_path' => env('DEMO_PHP_PATH', 'C:\\xampp\\php\\php.exe'),

    // 既存PHPファイルのディレクトリ（プロジェクトルートからの相対パス）
    'source_dir' => env('DEMO_SOURCE_DIR', base_path('../php-omoshiroi-code')),

    // メタデータ JSON のパス
    'metadata_path' => storage_path('app/demos.json'),

    // 実行タイムアウト（秒）
    'timeout' => 5,
];
```

---

## 7. データ設計（demos.json）

### 7.1 ファイル配置

```
backend/storage/app/demos.json
```

### 7.2 スキーマ

```json
{
  "categories": [
    {
      "id": "string（一意なカテゴリID）",
      "name": "string（表示名）",
      "description": "string（カテゴリの説明）",
      "demos": [
        {
          "filename": "string（PHPファイル名）",
          "title": "string（デモのタイトル）",
          "theme": "string（挙動の要約）",
          "description": "string（詳しい解説。Phase 3 で追加）",
          "runnable": "boolean（実行可能か。構文エラー等は false）"
        }
      ]
    }
  ]
}
```

### 7.3 runnable = false のファイル

以下のファイルは構文エラーや依存不足のため、実行不可としてマークする。

| ファイル名 | 理由 |
|-----------|------|
| `pdo_last_insert_id.php` | 構文エラー（未完成ファイル） |
| `sym/new/index.php` | 構文エラー（セミコロン位置のミス） |
| `datetime_create_from_format.php` | vendor/autoload.php が必要（composer install 未実行） |
| `no_global_in_function_required.php` | 単体では意味がない（require される側のファイル） |

---

## 8. 開発環境

### 8.1 確認済みの環境

| ツール | バージョン | 状態 |
|-------|-----------|------|
| PHP | 8.2.4（C:\xampp\php\php.exe） | ✅ インストール済み |
| Node.js | v24.13.0 | ✅ インストール済み |
| npm | 11.6.2 | ✅ インストール済み |
| Composer | — | ❌ **未インストール（要インストール）** |
| Git | 利用可能 | ✅ インストール済み |

### 8.2 環境構築手順（Phase 1 開始前に実施）

#### Step 1: Composer インストール

```bash
# Composer を PHP と同じディレクトリにインストールする
cd C:\xampp\php
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

実行後、`C:\xampp\php\composer.phar` が作成される。
以降は `C:\xampp\php\php.exe C:\xampp\php\composer.phar` で Composer が使える。

#### Step 2: Laravel プロジェクト作成

```bash
cd c:\Users\rei\phpTraining
C:\xampp\php\php.exe C:\xampp\php\composer.phar create-project laravel/laravel backend
```

#### Step 3: React プロジェクト作成

```bash
cd c:\Users\rei\phpTraining
npm create vite@latest frontend -- --template react-ts
cd frontend
npm install
```

#### Step 4: 開発サーバー起動確認

```bash
# ターミナル1: Laravel
cd backend
C:\xampp\php\php.exe artisan serve

# ターミナル2: React
cd frontend
npm run dev
```

- Laravel: http://localhost:8000
- React: http://localhost:5173

### 8.3 Vite プロキシ設定（CORS 回避）

```typescript
// frontend/vite.config.ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
```

#### なぜプロキシが必要か

- React（ポート 5173）から Laravel（ポート 8000）に直接リクエストすると、ブラウザの CORS 制約でブロックされる
- Vite のプロキシを使うと、React → Vite → Laravel と中継するため、ブラウザからは同じドメインに見える

---

## 9. ファイル行数制限

| ファイル種別 | 目安行数 | 理由 |
|------------|---------|------|
| Controller | 〜80行 | ロジックは Service に委譲。薄く保つ |
| Service | 〜150行 | 大きくなったら分割を検討 |
| React コンポーネント | 〜100行 | 大きくなったら子コンポーネントに分割 |
| カスタムフック | 〜50行 | 1つの責務に集中 |
| テスト | 制限なし | 網羅性を優先 |

---

## 10. 変更履歴

| 日付 | 変更内容 | 理由 |
|------|---------|------|
| 2026-02-18 | 初版作成 | 要件定義書に基づき基本設計を策定 |

---

_作成日: 2026-02-18_
_ステータス: レビュー待ち_
