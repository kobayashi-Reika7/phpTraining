import { useState } from "react";
import type {
  ContactFormData,
  ValidationErrors,
  Step,
  ApiResponse,
} from "../types";
import { validateContactForm } from "../utils/validation";

/**
 * コンタクトフォームのカスタムフック
 *
 * フォームの状態管理（値・エラー・ステップ・送信処理）を1つのフックにまとめる。
 * 各ページコンポーネントはこのフックの返り値だけを使えばよく、
 * ロジックがコンポーネントに散在しない。
 *
 * カスタムフックとは：
 * React の useState/useEffect 等を組み合わせた再利用可能なロジックの塊。
 * 関数名が "use" で始まるのが慣例。
 */

/** フォームの初期値 */
const initialFormData: ContactFormData = {
  name: "",
  email: "",
  comment: "",
  gender: "",
  kind: "",
  lang: [],
};

export function useContactForm() {
  const [formData, setFormData] = useState<ContactFormData>(initialFormData);
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [step, setStep] = useState<Step>("input");
  const [serverError, setServerError] = useState<string>("");
  const [submitting, setSubmitting] = useState(false);

  /**
   * フィールド値を更新する
   *
   * name, email 等の単一値フィールド用。
   * Partial<ContactFormData> を受け取り、該当フィールドだけ上書きする。
   */
  const updateField = (field: Partial<ContactFormData>) => {
    setFormData((prev) => ({ ...prev, ...field }));
    // 入力中はそのフィールドのエラーをクリア（入力しやすくなる）
    const fieldName = Object.keys(field)[0] as keyof ContactFormData;
    if (errors[fieldName]) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[fieldName];
        return next;
      });
    }
  };

  /**
   * バリデーション実行 → 確認画面へ遷移
   *
   * フロント側でバリデーションを行い、
   * エラーがなければ確認画面（confirm）に進む。
   */
  const validateAndConfirm = () => {
    const validationErrors = validateContactForm(formData);
    setErrors(validationErrors);

    // エラーが1つもなければ確認画面へ
    if (Object.keys(validationErrors).length === 0) {
      setStep("confirm");
    }
  };

  /** 確認画面から入力画面に戻る */
  const goBackToInput = () => {
    setStep("input");
  };

  /**
   * API にフォームデータを送信する
   *
   * fetch() で POST リクエストを送り、レスポンスに応じてステップを切り替える。
   * - 成功 → complete（完了画面）
   * - 422 → input に戻してサーバー側エラーを表示
   * - その他 → error（エラー画面）
   */
  const submit = async () => {
    setSubmitting(true);
    try {
      const response = await fetch("/api/contact", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(formData),
      });

      const result: ApiResponse = await response.json();

      if (result.success) {
        setStep("complete");
      } else if (response.status === 422 && result.errors) {
        // サーバー側バリデーションエラー → 入力画面に戻す
        const serverErrors: ValidationErrors = {};
        for (const [key, messages] of Object.entries(result.errors)) {
          serverErrors[key as keyof ContactFormData] = messages[0];
        }
        setErrors(serverErrors);
        setStep("input");
      } else {
        setServerError(result.message);
        setStep("error");
      }
    } catch {
      setServerError("通信エラーが発生しました。しばらく経ってからお試しください。");
      setStep("error");
    } finally {
      setSubmitting(false);
    }
  };

  /** フォームを初期状態にリセットして最初に戻る */
  const reset = () => {
    setFormData(initialFormData);
    setErrors({});
    setServerError("");
    setStep("input");
  };

  return {
    formData,
    errors,
    step,
    serverError,
    submitting,
    updateField,
    validateAndConfirm,
    goBackToInput,
    submit,
    reset,
  };
}
