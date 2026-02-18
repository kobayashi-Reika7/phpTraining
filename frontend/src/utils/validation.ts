import type { ContactFormData, ValidationErrors } from "../types";
import { GENDER_OPTIONS, KIND_OPTIONS, LANG_OPTIONS } from "../types";

/**
 * フロントエンド側のバリデーション
 *
 * バックエンド（ContactRequest.php）にも同じルールがあるが、
 * フロントでも検証する理由：
 * - ユーザーに即座にフィードバックを返せる（UX 向上）
 * - 不正なリクエストを API に送る前にブロックできる（通信量削減）
 *
 * ※セキュリティ上の最終防衛はバックエンド側のバリデーション
 */
export function validateContactForm(
  data: ContactFormData
): ValidationErrors {
  const errors: ValidationErrors = {};

  // name: 必須、最大20文字、タブ・改行禁止
  if (!data.name.trim()) {
    errors.name = "名前は必須です";
  } else if (data.name.length > 20) {
    errors.name = "名前は20文字以内で入力してください";
  } else if (/[\r\n\t]/.test(data.name)) {
    errors.name = "名前にタブや改行を含めることはできません";
  }

  // email: 必須、メール形式チェック
  if (!data.email.trim()) {
    errors.email = "メールアドレスは必須です";
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
    errors.email = "有効なメールアドレスを入力してください";
  }

  // comment: 必須、最大400文字
  if (!data.comment.trim()) {
    errors.comment = "コメントは必須です";
  } else if (data.comment.length > 400) {
    errors.comment = "コメントは400文字以内で入力してください";
  }

  // gender: 値があれば許可値チェック
  if (data.gender && !(GENDER_OPTIONS as readonly string[]).includes(data.gender)) {
    errors.gender = "無効な選択肢です";
  }

  // kind: 値があれば許可値チェック
  if (data.kind && !(KIND_OPTIONS as readonly string[]).includes(data.kind)) {
    errors.kind = "無効な選択肢です";
  }

  // lang: 各要素が許可値に含まれるかチェック
  if (data.lang.length > 0) {
    const invalid = data.lang.some(
      (l) => !(LANG_OPTIONS as readonly string[]).includes(l)
    );
    if (invalid) {
      errors.lang = "無効な選択肢です";
    }
  }

  return errors;
}
