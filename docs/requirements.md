# 要件定義書：PHP面白コード集 インタラクティブビューア

## 1. プロジェクト概要

### 1.1 プロジェクト名

**PHP Omoshiroi Viewer**（PHP面白コード集 インタラクティブビューア）

### 1.2 目的

既存の素PHPスクリプト集（`php-omoshiroi-code`）を題材にして、
**React 19 + Laravel 12** のフルスタック構成へ移行する練習を行う。

#### なぜ必要か

- 素PHP → モダンフレームワークへの移行手順を、小さな題材で体験する
- Laravel の API 設計、ルーティング、コントローラの基本を学ぶ
- React のコンポーネント設計、状態管理、API 通信の基本を学ぶ
- Strangler パターン（段階的移行）の考え方を実践する

### 1.3 移行方式

**ルート A：API 分離構成**を採用する。

| 層 | 技術 | 役割 |
|----|------|------|
| フロントエンド | React 19 + Vite | UI表示・ユーザー操作 |
| バックエンド | Laravel 12 (REST API) | PHPコード取得・実行結果返却 |
| データソース | ファイルベース（既存PHPファイル） | デモコード本体 |

#### なぜ API 分離構成か

- フロントとバックを独立して学べる
- 実務で最も応用が効く構成（モバイル対応も可能）
- Strangler パターンの典型例として理解しやすい

### 1.4 対象ユーザー

- PHPの基本を学習中の新人開発者（自分自身）
- ブラウザでアクセスして使う（ローカル環境）

### 1.5 対象外（スコープ外）

- 本番環境へのデプロイ
- ユーザー認証・ログイン機能
- データベース（Phase 1 では不要。将来拡張で追加可能）

---

## 2. 既存システムの分析

### 2.1 現在の構成

```
php-omoshiroi-code/
├── *.php              ... 45個の単体PHPスクリプト（CLIで実行）
├── sym/new/           ... 2個のPHPスクリプト
├── composer.json       ... 依存: nesbot/carbon のみ
├── docker-compose.yml  ... PHP 8.1-cli イメージ
└── README.md           ... 手動で php ファイル名.php と実行する説明
```

### 2.2 既存PHPファイルのカテゴリ分類（全47ファイル）

分析の結果、以下の7カテゴリに分類できる。

#### カテゴリ 1：配列（Array）— 13ファイル

PHPの配列はArrayとHashの両方を兼ねており、独特な挙動がある。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `array_at.php` | 存在しないキーへのアクセス | Warning + null 返却 |
| `array_false.php` | array_unique と false 値 | false 値の重複排除 |
| `array_in_array.php` | in_array の型変換 | strict モードの違い |
| `array_slice_hanuke.php` | 数値キーの歯抜けと slice | キーと順序の関係 |
| `array_undefined_offset.php` | 未定義オフセットのアクセス | Warning + null |
| `arraynull.php` | 未定義キーの参照パターン | isset / ?? / 直接参照の違い |
| `arraynullcomplex.php` | 未定義キー参照の実用例 | Warning の発生条件 |
| `arrayunique.php` | 多次元配列の unique | array_unique の挙動 |
| `empty_json.php` | 空配列の JSON 化 | [] vs {} の違い |
| `string_array_access.php` | 文字列への配列アクセス | string[0] の挙動 |
| `to_array.php` | (array) キャストと型ヒント | キャストは通るが型ヒントは通らない |
| `uniqud.php` | uniqid の一意性 | more_entropy オプション |
| `function_parameters_arra_is_pointer_or_not.php` | 配列の値渡し | 関数引数での配列コピー |

#### カテゴリ 2：型比較・型変換 — 9ファイル

PHPの緩い型比較（==）や暗黙の型変換による予想外の挙動。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `double_equal.php` | == の推移律が成り立たない | "true"==0, 0=="0", "true"!="0" |
| `false_null.php` | empty と is_null の違い | empty(false)→true, is_null(false)→false |
| `null_cast.php` | 未定義変数の参照 | Warning + NULL |
| `null_sprintf.php` | sprintf に null を渡す | 空文字として扱われる |
| `empty_emptyarray.php` | 空配列は empty か | empty([]) → true |
| `empty_string_is_null.php` | 空文字は null か | is_null("") → false |
| `isnumeric.php` | is_numeric vs is_float | 文字列 "0.234" の判定差 |
| `sizeofnull.php` | sizeof(null) | PHP 8 で TypeError |
| `count_not_coutable.php` | count("string") | PHP 7+ で Warning / PHP 8 で TypeError |

#### カテゴリ 3：関数 — 5ファイル

関数の引数、戻り値、スコープに関する挙動。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `fewMethodParameters.php` | 引数の過不足 | 多い→OK、少ない→Fatal |
| `php_returnvalue_void.php` | void 関数の return | return null は NG |
| `return_null_and_typehint.php` | void 型ヒントと null | return null; → Fatal |
| `no_global_in_function.php` | 関数内のスコープ | require 先の変数が見える |
| `named_arguments.php` | readonly プロパティ | PHP 8.1 コンストラクタプロモーション |

#### カテゴリ 4：クラス・オブジェクト — 5ファイル

継承、マジックメソッド、プロパティに関する挙動。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `extends.php` | メソッドシグネチャの互換性 | デフォルト引数の差で Fatal |
| `construct_extend.php` | コンストラクタの継承と名前空間 | 親子の __construct |
| `callable_class_property.php` | Callable プロパティ vs メソッド | $this->func() と ($this->func)() の違い |
| `magic_method_call_with_no_args.php` | __call マジックメソッド | 引数なし呼び出しの挙動 |
| `fatalexception.php` | require の Fatal Error | 存在しないファイルの require |

#### カテゴリ 5：日時（DateTime） — 2ファイル

PHPの日時処理における落とし穴。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `datetime_t.php` | 月の日数とうるう年 | format('t') の正確性 |
| `datetime_create_from_format.php` | createFromFormat の罠 | Y-m のみ指定時の挙動（※要 composer install） |

#### カテゴリ 6：文字列・正規表現 — 7ファイル

文字列操作と正規表現の注意点。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `strpos.php` | strpos の戻り値の罠 | 0 と false の比較 |
| `regex.php` | preg_match でのURL検証 | 正規表現パターン |
| `preg_backslash.php` | str_replace とバックスラッシュ | エスケープの挙動 |
| `htmlentities.php` | HTML エンティティ変換 | <, &amp; の変換 |
| `http_build_query.php` | クエリ文字列の生成 | 配列→URLクエリ |
| `decodeit.php` | base64 のエンコード/デコード | false/null の扱い |
| `dainyu.php` | === 厳密比較と代入 | 代入式の戻り値 |

#### カテゴリ 7：その他 — 6ファイル

定数スコープ、exec、シンボリックリンクなど。

| ファイル名 | テーマ | 挙動 |
|-----------|--------|------|
| `define_scope.php` | define の関数スコープ | 関数内 define は呼ぶまで未定義 |
| `exec.php` | exec の戻り値 | 外部コマンドの終了コード |
| `pdo_last_insert_id.php` | PDO（未完成ファイル） | 構文エラー |
| `sym/new/index.php` | require パス（構文エラー） | セミコロン位置のミス |
| `sym/new/hoge.php` | シンボリックリンク検証 | 正常出力 |
| `no_global_in_function_required.php` | require される側のファイル | 変数定義のみ |

---

## 3. 移行後のシステム要件

### 3.1 機能一覧

| ID | 機能名 | 説明 | 優先度 |
|----|--------|------|--------|
| F-01 | カテゴリ一覧表示 | 7カテゴリをサイドバーに表示する | 必須 |
| F-02 | デモファイル一覧表示 | 選択カテゴリに属するPHPファイルを一覧表示する | 必須 |
| F-03 | ソースコード表示 | 選択したPHPファイルのソースコードをシンタックスハイライト付きで表示する | 必須 |
| F-04 | 実行結果表示 | PHPファイルの実行結果（stdout / stderr）を表示する | 必須 |
| F-05 | 解説表示 | 各デモの「なぜ面白いのか」を解説テキストで表示する | 任意 |
| F-06 | 検索機能 | ファイル名・テーマでフィルタリングする | 任意 |

### 3.2 画面一覧

| ID | 画面名 | URL（React Router） | 説明 |
|----|--------|---------------------|------|
| S-01 | トップページ | `/` | カテゴリ一覧 + 概要説明 |
| S-02 | カテゴリ詳細 | `/category/:id` | 選択カテゴリのデモ一覧 |
| S-03 | デモ詳細 | `/demo/:filename` | コード + 実行結果 + 解説 |

### 3.3 API 一覧

| ID | メソッド | エンドポイント | 説明 | レスポンス例 |
|----|----------|---------------|------|-------------|
| A-01 | GET | `/api/categories` | カテゴリ一覧取得 | `[{id, name, count}]` |
| A-02 | GET | `/api/categories/:id/demos` | カテゴリ内デモ一覧 | `[{filename, title, theme}]` |
| A-03 | GET | `/api/demos/:filename` | デモ詳細取得 | `{filename, code, category, description}` |
| A-04 | POST | `/api/demos/:filename/run` | デモ実行 | `{stdout, stderr, exitCode}` |

### 3.4 データ構造

データベースは使用しない。代わりに、カテゴリ情報とメタデータを JSON ファイルで管理する。

```json
{
  "categories": [
    {
      "id": "array",
      "name": "配列（Array）",
      "description": "PHPの配列はArrayとHashの両方を兼ねており、独特な挙動がある",
      "demos": [
        {
          "filename": "array_at.php",
          "title": "存在しないキーへのアクセス",
          "theme": "Warning + null 返却",
          "description": "空の配列に存在しないキーでアクセスすると..."
        }
      ]
    }
  ]
}
```

---

## 4. 非機能要件

| 項目 | 要件 | なぜ必要か |
|------|------|-----------|
| 実行環境 | ローカル開発環境（Windows + XAMPP の PHP 8.2） | 学習用途のため |
| ブラウザ | Chrome / Edge（最新版） | 開発時に使用するブラウザ |
| レスポンス | API レスポンス 1秒以内 | ストレスなく学習するため |
| セキュリティ | PHP exec の実行対象を既存ファイルに限定する | 任意コード実行を防ぐため |
| コード品質 | 全ファイルにコメントを記載（新人が読んで理解できるレベル） | .cursorrules ルール準拠 |

---

## 5. 技術スタック

| レイヤー | 技術 | バージョン | なぜ選んだか |
|---------|------|-----------|-------------|
| フロントエンド | React | 19 系 | 最新の React で学習するため |
| ビルドツール | Vite | 最新 | React 公式が推奨するビルドツール |
| バックエンド | Laravel | 12 系 | 最新の Laravel で学習するため |
| PHP ランタイム | PHP | 8.2（XAMPP） | Laravel 12 の動作要件を満たす |
| パッケージ管理（JS） | npm | 最新 | Node.js 標準 |
| パッケージ管理（PHP） | Composer | 最新 | PHP 標準 |

---

## 6. 移行フェーズ（Strangler パターン）

### Phase 1：Laravel API 基盤（バックエンド）

- Laravel 12 プロジェクトを新規作成
- カテゴリ・デモのメタデータ JSON を作成
- API エンドポイント（A-01 〜 A-04）を実装
- PHPファイル実行機能を実装（既存ファイル限定）
- API 単体テスト（Feature テスト）

**完了条件：** curl / Postman で全 API が正常動作すること

### Phase 2：React フロントエンド（UI）

- Vite + React 19 プロジェクトを新規作成
- 画面コンポーネント（S-01 〜 S-03）を実装
- API 通信（fetch / axios）を実装
- シンタックスハイライト表示を実装

**完了条件：** ブラウザで全画面が表示され、コード + 実行結果が見えること

### Phase 3：解説・改善（任意）

- 各デモに解説テキストを追加（F-05）
- 検索・フィルタ機能を追加（F-06）
- UI/UX の改善（レスポンシブ対応など）

**完了条件：** 全デモに解説が付き、検索で絞り込めること

---

## 7. 制約事項・前提条件

| 項目 | 内容 |
|------|------|
| 開発環境 | Windows 10 + XAMPP（PHP 8.2.4） |
| PHP実行 | `C:\xampp\php\php.exe` を使用 |
| Node.js | 別途インストールが必要（未確認） |
| Composer | 別途インストールが必要（未確認） |
| 既存ファイル | `php-omoshiroi-code/` 配下のファイルは変更しない（読み取り専用として扱う） |
| 実行除外 | `pdo_last_insert_id.php`、`sym/new/index.php` は構文エラーのため実行対象外 |
| 実行除外 | `datetime_create_from_format.php` は composer install が必要（別途対応） |

---

## 8. 用語集

| 用語 | 説明 |
|------|------|
| Strangler パターン | 既存システムを一度に置き換えず、機能単位で段階的に移行する設計パターン |
| SPA | Single Page Application。ページ遷移なしで画面を切り替える Web アプリの構成 |
| REST API | HTTPメソッド（GET/POST等）でリソースを操作する API の設計スタイル |
| Vite | 高速な JavaScript ビルドツール。React の開発サーバーとして使う |
| シンタックスハイライト | ソースコードを色分けして見やすく表示する機能 |

---

_作成日: 2026-02-18_
_ステータス: レビュー待ち_
