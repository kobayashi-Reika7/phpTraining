/**
 * API のレスポンスに対応する TypeScript の型定義
 *
 * バックエンド（Laravel）が返す JSON の形をここで定義しておく。
 * これにより、フロントエンドで「どんなデータが来るか」が明確になり、
 * 存在しないプロパティへのアクセスをコンパイル時に検出できる。
 */

/** カテゴリ一覧 API（GET /api/categories）のレスポンス */
export interface Category {
  id: string;
  name: string;
  description: string;
  demo_count: number;
}

/** デモ一覧の各項目（GET /api/categories/:id/demos のレスポンス内） */
export interface DemoSummary {
  filename: string;
  title: string;
  theme: string;
  runnable: boolean;
}

/** カテゴリ内デモ一覧のレスポンス */
export interface CategoryDemos {
  category: {
    id: string;
    name: string;
  };
  demos: DemoSummary[];
}

/** デモ詳細 API（GET /api/demos/:filename）のレスポンス */
export interface DemoDetail {
  filename: string;
  title: string;
  theme: string;
  description: string;
  runnable: boolean;
  category: {
    id: string;
    name: string;
  };
  code: string;
}

/** デモ実行 API（POST /api/demos/:filename/run）のレスポンス */
export interface DemoRunResult {
  filename: string;
  stdout: string;
  stderr: string;
  exit_code: number;
  executed_at: string;
}
