import type { ReactNode } from "react";

/**
 * フォームフィールドの共通ラッパーコンポーネント
 *
 * ラベル・入力欄・エラーメッセージの表示パターンは各フィールドで共通なので、
 * 1つのコンポーネントにまとめて再利用する。
 * これにより InputPage のコードがすっきりし、見た目の統一感も保てる。
 *
 * Props:
 * - label: フィールドのラベル（例: "名前"）
 * - required: 必須マーク表示の有無
 * - error: バリデーションエラーメッセージ
 * - children: 入力要素（input, select, textarea, checkbox 等）
 */
interface FormFieldProps {
  label: string;
  required?: boolean;
  error?: string;
  children: ReactNode;
}

export function FormField({ label, required, error, children }: FormFieldProps) {
  return (
    <div className="form-field">
      <label className="form-label">
        {label}
        {required && <span className="required-badge">必須</span>}
      </label>
      <div className="form-input">{children}</div>
      {error && <p className="form-error">{error}</p>}
    </div>
  );
}
