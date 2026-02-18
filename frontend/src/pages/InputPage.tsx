import { FormField } from "../components/FormField";
import { FormLayout } from "../components/FormLayout";
import type { ContactFormData, ValidationErrors } from "../types";
import { GENDER_OPTIONS, KIND_OPTIONS, LANG_OPTIONS } from "../types";

/**
 * 入力画面コンポーネント
 *
 * ユーザーがフォームに情報を入力するステップ。
 * 6つのフィールド（名前・メール・コメント・性別・種類・言語）を表示し、
 * 「確認画面へ」ボタンでフロント側バリデーションを実行する。
 */
interface InputPageProps {
  formData: ContactFormData;
  errors: ValidationErrors;
  updateField: (field: Partial<ContactFormData>) => void;
  validateAndConfirm: () => void;
}

export function InputPage({
  formData,
  errors,
  updateField,
  validateAndConfirm,
}: InputPageProps) {
  /**
   * チェックボックス（lang）のトグル処理
   * 選択済みなら外す、未選択なら追加する
   */
  const toggleLang = (value: string) => {
    const next = formData.lang.includes(value)
      ? formData.lang.filter((l) => l !== value)
      : [...formData.lang, value];
    updateField({ lang: next });
  };

  /**
   * フォーム送信時（Enter キーやボタンクリック）の処理
   * ページ遷移（リロード）を防ぎ、バリデーション→確認画面を実行
   */
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    validateAndConfirm();
  };

  return (
    <FormLayout title="入力">
      <form onSubmit={handleSubmit} noValidate>
        {/* 名前（必須） */}
        <FormField label="名前" required error={errors.name}>
          <input
            type="text"
            value={formData.name}
            onChange={(e) => updateField({ name: e.target.value })}
            placeholder="例: 田中太郎"
            maxLength={20}
            className={errors.name ? "input-error" : ""}
          />
        </FormField>

        {/* メールアドレス（必須） */}
        <FormField label="メールアドレス" required error={errors.email}>
          <input
            type="email"
            value={formData.email}
            onChange={(e) => updateField({ email: e.target.value })}
            placeholder="例: tanaka@example.com"
            className={errors.email ? "input-error" : ""}
          />
        </FormField>

        {/* 性別（任意） */}
        <FormField label="性別" error={errors.gender}>
          <div className="radio-group">
            {GENDER_OPTIONS.map((option) => (
              <label key={option} className="radio-label">
                <input
                  type="radio"
                  name="gender"
                  value={option}
                  checked={formData.gender === option}
                  onChange={(e) => updateField({ gender: e.target.value })}
                />
                {option}
              </label>
            ))}
            {formData.gender && (
              <button
                type="button"
                className="clear-button"
                onClick={() => updateField({ gender: "" })}
              >
                クリア
              </button>
            )}
          </div>
        </FormField>

        {/* 問い合わせの種類（任意） */}
        <FormField label="問い合わせの種類" error={errors.kind}>
          <select
            value={formData.kind}
            onChange={(e) => updateField({ kind: e.target.value })}
          >
            {KIND_OPTIONS.map((option) => (
              <option key={option} value={option}>
                {option || "選択してください"}
              </option>
            ))}
          </select>
        </FormField>

        {/* 使用プログラミング言語（任意・複数選択） */}
        <FormField label="使用プログラミング言語" error={errors.lang}>
          <div className="checkbox-group">
            {LANG_OPTIONS.map((option) => (
              <label key={option} className="checkbox-label">
                <input
                  type="checkbox"
                  value={option}
                  checked={formData.lang.includes(option)}
                  onChange={() => toggleLang(option)}
                />
                {option}
              </label>
            ))}
          </div>
        </FormField>

        {/* コメント（必須） */}
        <FormField label="コメント" required error={errors.comment}>
          <textarea
            value={formData.comment}
            onChange={(e) => updateField({ comment: e.target.value })}
            placeholder="お問い合わせ内容を入力してください"
            rows={5}
            maxLength={400}
            className={errors.comment ? "input-error" : ""}
          />
          <p className="char-count">{formData.comment.length} / 400</p>
        </FormField>

        <div className="form-actions">
          <button type="submit" className="btn btn-primary">
            確認画面へ
          </button>
        </div>
      </form>
    </FormLayout>
  );
}
