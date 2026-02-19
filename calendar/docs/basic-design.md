# 基本設計書 — 予約カレンダー（Laravel + React マイグレーション版）

## 1. ディレクトリ構成

```
calendar/
├── docs/                          # ドキュメント
│   ├── requirements.md            # 要件定義書
│   └── basic-design.md            # 基本設計書（本ファイル）
│
├── backend/                       # Laravel 12 API サーバー
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       ├── AuthController.php        # 認証（登録/ログイン/ログアウト/ユーザー情報）
│   │   │   │       ├── SlotController.php         # 空き枠取得（日別/週別）
│   │   │   │       ├── ReservationController.php  # 予約 CRUD（作成/一覧/削除）
│   │   │   │       └── DoctorController.php       # 医師一覧（診療科別）
│   │   │   ├── Requests/
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── LoginRequest.php           # ログインバリデーション
│   │   │   │   │   └── RegisterRequest.php        # 登録バリデーション
│   │   │   │   └── StoreReservationRequest.php    # 予約作成バリデーション
│   │   │   └── Middleware/
│   │   │       └── （Laravel 標準 throttle 等を利用）
│   │   ├── Models/
│   │   │   ├── User.php                           # ユーザー（Sanctum 連携）
│   │   │   ├── Doctor.php                         # 担当医
│   │   │   ├── Reservation.php                    # 予約
│   │   │   └── BookedSlot.php                     # 予約済み枠（二重予約防止）
│   │   └── Services/
│   │       ├── HolidayService.php                 # 日本の祝日判定
│   │       ├── AvailabilityService.php            # 空き枠算出ロジック
│   │       └── ReservationService.php             # 予約作成・キャンセルのビジネスロジック
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── xxxx_create_users_table.php        # Laravel 標準
│   │   │   ├── xxxx_create_doctors_table.php
│   │   │   ├── xxxx_create_reservations_table.php
│   │   │   └── xxxx_create_booked_slots_table.php
│   │   └── seeders/
│   │       └── DoctorSeeder.php                   # 17 名の医師データ投入
│   ├── routes/
│   │   └── api.php                                # API ルート定義
│   ├── .env.example
│   └── composer.json
│
└── frontend/                      # React 19 + TypeScript SPA
    ├── src/
    │   ├── main.tsx                               # エントリポイント
    │   ├── App.tsx                                 # ルーティング + 認証コンテキスト
    │   ├── App.css                                 # グローバルスタイル
    │   ├── pages/
    │   │   ├── TopPage.tsx                         # トップページ
    │   │   ├── LoginPage.tsx                       # ログイン
    │   │   ├── SignupPage.tsx                      # 新規登録
    │   │   ├── MenuPage.tsx                        # メニュー
    │   │   ├── CalendarPage.tsx                    # カレンダー（月表示）
    │   │   ├── ReservationFormPage.tsx             # 予約フォーム（週ビュー + 時間枠）
    │   │   ├── ReserveConfirmPage.tsx              # 予約確認・確定
    │   │   └── MyReservationsPage.tsx              # 予約一覧（変更・キャンセル）
    │   ├── components/
    │   │   ├── Calendar.tsx                        # 月カレンダーコンポーネント
    │   │   ├── TimeSlot.tsx                        # 時間枠ボタン（○/△/×）
    │   │   ├── DepartmentListSelector.tsx          # 診療科選択
    │   │   ├── Breadcrumb.tsx                      # パンくずリスト
    │   │   ├── ReservationStepHeader.tsx           # ステップインジケーター
    │   │   ├── ProtectedRoute.tsx                  # 認証ガード
    │   │   └── ErrorBoundary.tsx                   # エラー境界
    │   ├── hooks/
    │   │   ├── useAuth.ts                          # 認証状態管理（Sanctum ベース）
    │   │   └── useApi.ts                           # API 通信共通フック
    │   ├── services/
    │   │   ├── api.ts                              # fetch ラッパー（/api プロキシ経由）
    │   │   ├── authService.ts                      # 認証 API 呼び出し
    │   │   ├── reservationService.ts               # 予約 CRUD API 呼び出し
    │   │   ├── slotService.ts                      # 空き枠 API 呼び出し
    │   │   └── doctorService.ts                    # 医師 API 呼び出し
    │   ├── types/
    │   │   ├── auth.ts                             # User, LoginPayload, RegisterPayload
    │   │   ├── reservation.ts                      # Reservation, CreateReservationPayload
    │   │   ├── slot.ts                             # SlotItem, AvailabilityResponse
    │   │   └── doctor.ts                           # Doctor
    │   ├── constants/
    │   │   └── masterData.ts                       # カテゴリ・診療科・時間枠定義
    │   └── utils/
    │       ├── holiday.ts                          # 祝日判定（フロント側。バッジ表示用）
    │       └── schedule.ts                         # 曜日・スケジュールユーティリティ
    ├── package.json
    ├── tsconfig.json
    └── vite.config.ts                             # /api → localhost:8000 プロキシ
```

---

## 2. データベース設計

### 2.1 ER 図

```
┌─────────────────┐       ┌──────────────────────┐       ┌─────────────────┐
│     users        │       │    reservations       │       │    doctors       │
├─────────────────┤       ├──────────────────────┤       ├─────────────────┤
│ id          PK   │◄──┐  │ id              PK    │  ┌──►│ id          PK   │
│ name             │   │  │ user_id         FK ───┘  │   │ name             │
│ email     UQ     │   │  │ doctor_id       FK ──────┘   │ department       │
│ password         │   │  │ department            │       │ schedules  JSON  │
│ created_at       │   │  │ date                  │       │ created_at       │
│ updated_at       │   │  │ time                  │       │ updated_at       │
└─────────────────┘   │  │ purpose               │       └─────────────────┘
                       │  │ created_at            │
                       │  │ updated_at            │       ┌─────────────────┐
                       │  └──────────────────────┘       │  booked_slots    │
                       │                                  ├─────────────────┤
                       │                                  │ id          PK   │
                       └──────────────────────────────────│ user_id     FK   │
                                                          │ doctor_id   FK   │
                                                          │ reservation_id   │
                                                          │ department       │
                                                          │ date             │
                                                          │ time             │
                                                          │ created_at       │
                                                          │ updated_at       │
                                                          │                  │
                                                          │ UQ(doctor_id,    │
                                                          │    date, time)   │
                                                          └─────────────────┘
```

### 2.2 テーブル定義

#### users テーブル

| カラム | 型 | 制約 | 備考 |
|--------|-----|------|------|
| id | bigint unsigned | PK, AUTO_INCREMENT | |
| name | varchar(255) | NOT NULL | ユーザー名 |
| email | varchar(255) | NOT NULL, UNIQUE | ログイン ID |
| email_verified_at | timestamp | NULLABLE | Laravel 標準 |
| password | varchar(255) | NOT NULL | bcrypt ハッシュ |
| remember_token | varchar(100) | NULLABLE | Laravel 標準 |
| created_at | timestamp | | |
| updated_at | timestamp | | |

#### doctors テーブル

| カラム | 型 | 制約 | 備考 |
|--------|-----|------|------|
| id | varchar(50) | PK | 例: `doc_cardiology_01` |
| name | varchar(100) | NOT NULL | 医師名 |
| department | varchar(100) | NOT NULL, INDEX | 診療科名（masterData と一致） |
| schedules | json | NOT NULL | 曜日別の勤務時間配列 |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**schedules JSON 構造:**
```json
{
  "mon": ["09:00", "09:15", "09:30", ...],
  "tue": ["09:00", "09:15", ...],
  "wed": [],
  "thu": ["13:00", "13:15", ...],
  "fri": ["09:00", "09:15", ...],
  "sat": [],
  "sun": []
}
```

#### reservations テーブル

| カラム | 型 | 制約 | 備考 |
|--------|-----|------|------|
| id | bigint unsigned | PK, AUTO_INCREMENT | |
| user_id | bigint unsigned | FK → users.id, NOT NULL | |
| doctor_id | varchar(50) | FK → doctors.id, NOT NULL | 自動割当された医師 |
| department | varchar(100) | NOT NULL | 診療科名 |
| date | date | NOT NULL, INDEX | 予約日 |
| time | varchar(5) | NOT NULL | 予約時刻 "HH:mm" |
| purpose | varchar(20) | DEFAULT '' | 初診 / 再診 |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**インデックス:**
- `idx_reservations_user`: (user_id)
- `idx_reservations_lookup`: (department, date, time)
- `idx_reservations_doctor_date`: (doctor_id, date)

#### booked_slots テーブル

| カラム | 型 | 制約 | 備考 |
|--------|-----|------|------|
| id | bigint unsigned | PK, AUTO_INCREMENT | |
| doctor_id | varchar(50) | FK → doctors.id, NOT NULL | |
| date | date | NOT NULL | |
| time | varchar(5) | NOT NULL | |
| department | varchar(100) | NOT NULL | |
| user_id | bigint unsigned | FK → users.id, NOT NULL | |
| reservation_id | bigint unsigned | NULLABLE | 予約確定後に紐付け |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**UNIQUE 制約:** `uq_booked_slots_doctor_date_time` (doctor_id, date, time)

この UNIQUE 制約が二重予約防止の要。同一医師・同一日時に 2 つ目の INSERT が来ると DB エラーとなり、トランザクションがロールバックされる。

---

## 3. API 設計

### 3.1 認証 API

| メソッド | エンドポイント | 認証 | 説明 |
|----------|---------------|------|------|
| POST | `/api/register` | 不要 | ユーザー登録 |
| POST | `/api/login` | 不要 | ログイン（トークン発行） |
| POST | `/api/logout` | 必要 | ログアウト（トークン無効化） |
| GET | `/api/user` | 必要 | ログインユーザー情報取得 |

#### POST /api/register

**リクエスト:**
```json
{
  "name": "田中 太郎",
  "email": "tanaka@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**レスポンス (201):**
```json
{
  "user": { "id": 1, "name": "田中 太郎", "email": "tanaka@example.com" },
  "token": "1|xxxxxxxxxxxxxxxxxx"
}
```

**バリデーション:**
- name: 必須, 最大 255 文字
- email: 必須, メール形式, ユニーク
- password: 必須, 6 文字以上, confirmed

#### POST /api/login

**リクエスト:**
```json
{
  "email": "tanaka@example.com",
  "password": "password123"
}
```

**レスポンス (200):**
```json
{
  "user": { "id": 1, "name": "田中 太郎", "email": "tanaka@example.com" },
  "token": "2|xxxxxxxxxxxxxxxxxx"
}
```

**エラー (401):**
```json
{
  "message": "メールアドレスまたはパスワードが正しくありません"
}
```

#### POST /api/logout

**ヘッダー:** `Authorization: Bearer <token>`

**レスポンス (200):**
```json
{
  "message": "ログアウトしました"
}
```

#### GET /api/user

**ヘッダー:** `Authorization: Bearer <token>`

**レスポンス (200):**
```json
{
  "id": 1,
  "name": "田中 太郎",
  "email": "tanaka@example.com"
}
```

---

### 3.2 空き枠 API

| メソッド | エンドポイント | 認証 | 説明 |
|----------|---------------|------|------|
| GET | `/api/slots` | 任意 | 1 日分の空き枠取得 |
| GET | `/api/slots/week` | 任意 | 複数日の空き枠一括取得（最大 14 日） |

#### GET /api/slots

**クエリパラメータ:**
- `department` (必須): 診療科名（例: "循環器内科"）
- `date` (必須): 日付 "YYYY-MM-DD"

**レスポンス (200):**
```json
{
  "date": "2026-02-20",
  "is_holiday": false,
  "reservable": true,
  "reason": null,
  "slots": [
    { "time": "09:00", "reservable": true },
    { "time": "09:15", "reservable": true },
    { "time": "09:30", "reservable": false },
    ...
  ]
}
```

**reason の値:**
- `null`: 予約可能
- `"past"`: 過去の日付
- `"holiday"`: 祝日
- `"closed"`: 診療科の休診日

#### GET /api/slots/week

**クエリパラメータ:**
- `department` (必須): 診療科名
- `dates` (必須): カンマ区切りの日付（最大 14 日）

**レスポンス (200):**
```json
[
  { "date": "2026-02-16", "is_holiday": false, "reservable": true, "reason": null, "slots": [...] },
  { "date": "2026-02-17", "is_holiday": false, "reservable": true, "reason": null, "slots": [...] },
  ...
]
```

---

### 3.3 予約 API

| メソッド | エンドポイント | 認証 | 説明 |
|----------|---------------|------|------|
| GET | `/api/reservations` | 必要 | ログインユーザーの予約一覧 |
| POST | `/api/reservations` | 必要 | 予約作成 |
| DELETE | `/api/reservations/{id}` | 必要 | 予約キャンセル |

#### POST /api/reservations

**ヘッダー:** `Authorization: Bearer <token>`

**リクエスト:**
```json
{
  "department": "循環器内科",
  "date": "2026-02-20",
  "time": "09:00",
  "purpose": "初診"
}
```

**バリデーション:**
- department: 必須, 最大 100 文字
- date: 必須, YYYY-MM-DD 形式, 未来日
- time: 必須, HH:mm 形式
- purpose: 任意, 最大 20 文字

**レスポンス (201):**
```json
{
  "id": 1,
  "department": "循環器内科",
  "date": "2026-02-20",
  "time": "09:00"
}
```

**エラー (400):**
```json
{
  "message": "この時間は現在予約できません"
}
```

**予約作成フロー（バックエンド内部）:**
1. 日付バリデーション（過去日、祝日、当日の過ぎた時刻）
2. ユーザー重複チェック（同一診療科・日時）
3. 空き医師を検索
4. DB トランザクション開始
5. `booked_slots` INSERT（UNIQUE 制約で競合を排除）
6. `reservations` INSERT
7. `booked_slots.reservation_id` を更新
8. コミット

#### GET /api/reservations

**ヘッダー:** `Authorization: Bearer <token>`

**レスポンス (200):**
```json
[
  {
    "id": 1,
    "department": "循環器内科",
    "date": "2026-02-20",
    "time": "09:00",
    "purpose": "初診",
    "doctor_name": "山田 太郎",
    "created_at": "2026-02-19T10:00:00Z"
  }
]
```

#### DELETE /api/reservations/{id}

**ヘッダー:** `Authorization: Bearer <token>`

**レスポンス (200):**
```json
{
  "ok": true,
  "id": 1
}
```

**キャンセルフロー:**
1. 予約を取得（本人確認）
2. `booked_slots` の該当レコードを削除
3. `reservations` の該当レコードを削除

---

### 3.4 医師 API

| メソッド | エンドポイント | 認証 | 説明 |
|----------|---------------|------|------|
| GET | `/api/doctors` | 任意 | 医師一覧（診療科でフィルタ可） |

#### GET /api/doctors

**クエリパラメータ:**
- `department` (任意): 診療科名でフィルタ

**レスポンス (200):**
```json
[
  {
    "id": "doc_cardiology_01",
    "name": "山田 太郎",
    "department": "循環器内科",
    "schedules": { "mon": ["09:00", "09:15", ...], ... }
  }
]
```

---

## 4. 認証設計

### 4.1 方式

Laravel Sanctum のトークン認証（API トークン方式）を採用。

**理由:**
- フロント（React SPA）とバックエンド（Laravel API）が別プロセスで動作するため
- 既存ワークスペース（contactform / sample）のパターンと合致
- セッションベースの CSRF 管理が不要でシンプル

### 4.2 フロー

```
[React SPA]                              [Laravel API]
    │                                         │
    │  POST /api/register                     │
    │  {email, password, ...}                 │
    │ ──────────────────────────────────────►  │
    │                                         │ User::create() + createToken()
    │  ◄──────────────────────────────────────│
    │  {user, token}                          │
    │                                         │
    │  (トークンを localStorage に保存)        │
    │                                         │
    │  GET /api/user                          │
    │  Authorization: Bearer <token>          │
    │ ──────────────────────────────────────►  │
    │                                         │ auth:sanctum ミドルウェア
    │  ◄──────────────────────────────────────│
    │  {id, name, email}                      │
```

### 4.3 フロント側の認証状態管理

```typescript
// hooks/useAuth.ts（概念）
type AuthState = {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  signup: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
};
```

- `user === null` かつ `loading === false`: 未ログイン
- `user !== null`: ログイン済み
- `loading === true`: 認証状態確認中

---

## 5. ビジネスロジック設計

### 5.1 祝日判定（HolidayService）

元プロジェクトの Python 実装 `_is_japanese_holiday()` を PHP に完全移植する。

**判定対象:**

| 種類 | 祝日 | 日付 |
|------|------|------|
| 固定 | 元日 | 1月1日 |
| 固定 | 建国記念の日 | 2月11日 |
| 固定 | 天皇誕生日 | 2月23日 |
| 固定 | 昭和の日 | 4月29日 |
| 固定 | 憲法記念日 | 5月3日 |
| 固定 | みどりの日 | 5月4日 |
| 固定 | こどもの日 | 5月5日 |
| 固定 | 山の日 | 8月11日 |
| 固定 | 文化の日 | 11月3日 |
| 固定 | 勤労感謝の日 | 11月23日 |
| 移動 | 成人の日 | 1月第2月曜日 |
| 移動 | 海の日 | 7月第3月曜日 |
| 移動 | 敬老の日 | 9月第3月曜日 |
| 移動 | スポーツの日 | 10月第2月曜日 |
| 計算 | 春分の日 | `floor(20.8431 + 0.242194 * (year - 1980) - floor((year - 1980) / 4))` |
| 計算 | 秋分の日 | `floor(23.2488 + 0.242194 * (year - 1980) - floor((year - 1980) / 4))` |

**振替休日:** 祝日が日曜の場合、翌月曜日が振替休日。

**フロントエンド側にも同一ロジック** を TypeScript で実装（カレンダーの祝日バッジ表示用）。

### 5.2 空き枠算出（AvailabilityService）

```
入力: department(診療科), date(日付), [user_id]
  ↓
1. 過去日チェック → 過去なら reason: "past"
2. 祝日チェック → 祝日なら reason: "holiday"
3. 該当診療科の医師を取得
4. 各時間枠 (09:00〜16:45) について:
   a. 医師の勤務スケジュールを確認
   b. booked_slots テーブルで予約済みを確認
   c. 空き医師が1名以上 → reservable: true
   d. 全医師が埋まっている → reservable: false
5. ユーザー ID があれば自身の予約済み枠も reservable: false に
  ↓
出力: { date, is_holiday, reservable, reason, slots[] }
```

**週別取得の最適化:**
- 1 クエリで診療科の全医師を取得
- 1 クエリで対象日付範囲の booked_slots を取得
- PHP 側でループ結合（N+1 回避）

### 5.3 予約作成（ReservationService）

```
入力: department, date, time, purpose, user_id
  ↓
1. バリデーション
   - 過去日 → エラー
   - 祝日 → エラー
   - 当日の過ぎた時刻 → エラー
   - 同一ユーザーの重複 → エラー
2. 空き医師を検索
   - 該当診療科 + 該当曜日の勤務医を取得
   - booked_slots で埋まっていない医師を抽出
   - 0 名 → エラー
3. DB::transaction 開始
   - booked_slots INSERT（UNIQUE 制約で競合排除）
   - reservations INSERT
   - booked_slots.reservation_id を UPDATE
4. コミット
  ↓
出力: { id, department, date, time }
```

**二重予約防止の仕組み:**

| レイヤー | 方式 | 目的 |
|---------|------|------|
| DB | `booked_slots` の UNIQUE(doctor_id, date, time) | 同一医師・同一日時を物理的に排除 |
| DB | トランザクション (READ COMMITTED 以上) | INSERT→UPDATE の原子性を保証 |
| App | 空き医師ループ + try/catch | UNIQUE 違反時に次の医師を試行 |

元プロジェクトの `threading.Lock` は不要。RDB のトランザクション + UNIQUE 制約がより堅牢。

### 5.4 予約キャンセル（ReservationService）

```
入力: reservation_id, user_id
  ↓
1. reservations から取得（user_id 一致を確認 → 認可）
2. DB::transaction 開始
   - booked_slots DELETE (doctor_id + date + time で特定)
   - reservations DELETE
3. コミット
  ↓
出力: { ok: true, id }
```

---

## 6. フロントエンド設計

### 6.1 技術構成

| 項目 | 選定 | 理由 |
|------|------|------|
| フレームワーク | React 19 | ワークスペース標準 |
| 言語 | TypeScript | ワークスペース標準。型安全性 |
| ルーティング | React Router (最新) | 複数画面の SPA |
| ビルド | Vite 7 | ワークスペース標準 |
| API 通信 | fetch API | ワークスペース標準（axios 不使用） |
| CSS | 独自 CSS（レスポンシブ） | 病院テーマ。モバイルファースト + メディアクエリ |
| コード分割 | React.lazy() + Suspense | 初期読み込み最適化 |

### 6.2 状態管理

| 状態 | 管理方法 | 理由 |
|------|---------|------|
| 認証 | React Context (AuthContext) | 全画面で参照。頻繁な更新なし |
| フォーム入力 | useState (ローカル) | 画面固有。Context 不要 |
| 画面間データ受渡し | React Router state | 遷移元 → 遷移先へ state を渡す |
| API レスポンスキャッシュ | useState + TTL 管理 | 空き枠データの再取得抑制（5 分 TTL） |

### 6.3 API 通信パターン

```typescript
// services/api.ts（概念）
const API_BASE = '/api';

async function apiFetch<T>(path: string, options?: RequestInit): Promise<T> {
  const token = localStorage.getItem('token');
  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  const response = await fetch(`${API_BASE}${path}`, { ...options, headers });

  if (!response.ok) {
    // 401 → ログアウト処理
    // 400 → バリデーションエラー表示
    // 500 → 汎用エラー表示
    throw new ApiError(response.status, await response.json());
  }

  return response.json();
}
```

### 6.4 Vite プロキシ設定

```typescript
// vite.config.ts
export default defineConfig({
  server: {
    port: 5200,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
});
```

### 6.5 UI/UX ルール（元プロジェクトから継承 + PC 対応拡張）

- 時間枠: ○（空き 2 名以上）/ △（残り 1 名）/ ×（満枠）
- 確認ボタン: 全条件を満たすまで disabled + グレー表示
- 予約ボタン: スマホでは画面下部に固定、PC では自然な位置に配置
- エラーメッセージ: 即時表示（後出し NG）
- 配色: 白・淡いブルー・グリーン
- フォント: 16px 以上
- 段階的表示: 情報過多にしない

### 6.6 レスポンシブデザイン方針

**ブレークポイント:**

| 名称 | 幅 | レイアウト |
|------|-----|-----------|
| スマホ | 〜767px | 1 カラム縦積み。フォーム要素はフル幅。週ビューは横スクロール or 3 日表示 |
| タブレット | 768〜1023px | 適宜 2 カラム。週ビューは 5 日表示。カレンダーとフォームを横並び可 |
| PC | 1024px〜 | max-width: 1200px のコンテナ。サイドバー or ワイドレイアウト。週ビューは余裕を持った 5 日表示 |

**画面ごとのレスポンシブ対応:**

| 画面 | スマホ | PC |
|------|--------|-----|
| トップページ | 縦積み。診療科はアコーディオン | 2〜3 カラムカード。病院情報をサイドに配置 |
| ログイン / 新規登録 | フル幅フォーム、中央寄せ | 最大幅 480px のカード、画面中央に配置 |
| メニュー | 縦積みボタン | 横並びカード（2 カラム） |
| カレンダー | フル幅月表示 | 左にカレンダー、右に選択日の情報（2 カラム） |
| 予約フォーム | 縦積み。週ビューは横スクロール | 左にフォーム（診療科・種別）、右に週ビュー時間枠グリッド |
| 予約確認 | 縦積みカード | 最大幅 600px のカード、中央配置 |
| 予約一覧 | カード縦積み | テーブル表示 or ワイドカード。日付・時間・診療科を横並び |

**CSS 実装方針:**
- CSS メディアクエリでブレークポイントを切り替え（モバイルファースト: `min-width` 基準）
- CSS Grid / Flexbox を活用
- タッチターゲット: スマホでは最小 44x44px を確保
- ホバー状態: PC ではボタン・リンクにホバーエフェクトを追加（スマホでは不要）

---

## 7. マイグレーション実施フェーズ

### Phase 1: インフラ基盤（0.5 日）

| # | タスク |
|---|--------|
| 1-1 | `laravel new backend` で Laravel 12 プロジェクト作成 |
| 1-2 | `composer require laravel/sanctum` |
| 1-3 | マイグレーション作成（users / doctors / reservations / booked_slots） |
| 1-4 | Eloquent モデル + リレーション定義 |
| 1-5 | DoctorSeeder 作成（17 名の医師データ） |
| 1-6 | `.env` 設定（DB_CONNECTION=sqlite） |

### Phase 2: 認証 API（0.5 日）

| # | タスク |
|---|--------|
| 2-1 | AuthController（register / login / logout / user） |
| 2-2 | LoginRequest / RegisterRequest バリデーション |
| 2-3 | Sanctum 設定（config/sanctum.php, CORS） |
| 2-4 | routes/api.php 認証ルート |

### Phase 3: コア API（1.5 日）

| # | タスク |
|---|--------|
| 3-1 | HolidayService（祝日判定ロジック完全移植） |
| 3-2 | AvailabilityService（空き枠算出） |
| 3-3 | ReservationService（予約作成・キャンセル） |
| 3-4 | SlotController（GET /api/slots, GET /api/slots/week） |
| 3-5 | ReservationController（POST, GET, DELETE） |
| 3-6 | DoctorController（GET /api/doctors） |
| 3-7 | StoreReservationRequest バリデーション |

### Phase 4: フロントエンド（1.5 日）

| # | タスク |
|---|--------|
| 4-1 | `npm create vite@latest frontend -- --template react-ts` |
| 4-2 | vite.config.ts（プロキシ設定） |
| 4-3 | types/ 型定義 |
| 4-4 | constants/masterData.ts |
| 4-5 | utils/holiday.ts, utils/schedule.ts |
| 4-6 | hooks/useAuth.ts, hooks/useApi.ts |
| 4-7 | services/ API 通信層（Firebase SDK → fetch） |
| 4-8 | pages/ 8 画面を TSX で実装 |
| 4-9 | components/ 共通コンポーネント |
| 4-10 | App.tsx ルーティング + 認証ガード |
| 4-11 | CSS（病院テーマ移植） |

### Phase 5: 結合・仕上げ（0.5 日）

| # | タスク |
|---|--------|
| 5-1 | E2E 動作確認（登録→ログイン→予約→確認→キャンセル） |
| 5-2 | 二重予約防止テスト |
| 5-3 | バリデーションエラー表示確認 |
| 5-4 | レートリミット設定 |
| 5-5 | calendar_temp/ 削除 |

---

## 8. 起動手順（完成後）

### バックエンド

```bash
cd calendar/backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DoctorSeeder
php artisan serve              # http://localhost:8000
```

### フロントエンド

```bash
cd calendar/frontend
npm install
npm run dev                    # http://localhost:5200
```

ブラウザで http://localhost:5200 を開く。
