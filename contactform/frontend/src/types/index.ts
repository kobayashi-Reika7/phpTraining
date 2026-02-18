/**
 * コンタクトフォームの型定義
 *
 * TypeScript の型を定義しておくと、
 * フォームデータや API レスポンスの構造をコード上で明確にでき、
 * 入力ミスや型の不整合をコンパイル時に検出できる。
 */

/** フォームの入力データ（各フィールドに対応） */
export interface ContactFormData {
  name: string;
  email: string;
  comment: string;
  gender: string;
  kind: string;
  lang: string[];
}

/** API レスポンス（成功時） */
export interface ApiSuccessResponse {
  success: true;
  message: string;
}

/** API レスポンス（エラー時） */
export interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

/** API レスポンスの型（成功 or エラー） */
export type ApiResponse = ApiSuccessResponse | ApiErrorResponse;

/**
 * バリデーションエラーの型
 *
 * Partial<Record<keyof T, string>> は「T のキーすべてに対して string 値が入るかもしれないオブジェクト」。
 * エラーがあるフィールドだけキーが存在し、値にエラーメッセージが入る。
 */
export type ValidationErrors = Partial<Record<keyof ContactFormData, string>>;

/** 画面ステップ（useState で管理する SPA のページ遷移） */
export type Step = "input" | "confirm" | "complete" | "error";

/** フォームの選択肢定義 */
export const GENDER_OPTIONS = ["男性", "女性"] as const;
export const KIND_OPTIONS = [
  "",
  "製品購入前のお問い合わせ",
  "製品購入後のお問い合わせ",
  "その他",
] as const;
export const LANG_OPTIONS = ["PHP", "Perl", "Python"] as const;
