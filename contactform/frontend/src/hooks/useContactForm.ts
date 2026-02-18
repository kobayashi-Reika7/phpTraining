import { useState, useCallback } from "react";
import type {
  ContactFormData,
  ValidationErrors,
  Step,
  ApiResponse,
} from "../types";
import { validateContactForm } from "../utils/validation";
import { scrollToTop } from "../utils/scroll";

/**
 * コンタクトフォームのカスタムフック
 *
 * フォームの状態管理（値・エラー・ステップ・送信処理）を1つのフックにまとめる。
 * 各ページコンポーネントはこのフックの返り値だけを使えばよく、
 * ロジックがコンポーネントに散在しない。
 *
 * useCallback でハンドラをメモ化し、子コンポーネントの不要な再レンダリングを防ぐ。
 */

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
   * 入力中はそのフィールドのエラーをクリアして入力しやすくする。
   */
  const updateField = useCallback(
    (field: Partial<ContactFormData>) => {
      setFormData((prev) => ({ ...prev, ...field }));
      const fieldName = Object.keys(field)[0] as keyof ContactFormData;
      if (errors[fieldName]) {
        setErrors((prev) => {
          const next = { ...prev };
          delete next[fieldName];
          return next;
        });
      }
    },
    [errors]
  );

  /**
   * バリデーション実行 → 確認画面へ遷移
   */
  const validateAndConfirm = useCallback(() => {
    const validationErrors = validateContactForm(formData);
    setErrors(validationErrors);

    if (Object.keys(validationErrors).length === 0) {
      setStep("confirm");
    }
    scrollToTop();
  }, [formData]);

  /** 確認画面から入力画面に戻る */
  const goBackToInput = useCallback(() => {
    setStep("input");
    scrollToTop();
  }, []);

  /**
   * API にフォームデータを送信する
   *
   * - 成功 → complete（完了画面）
   * - 422 → input に戻してサーバー側エラーを表示
   * - その他 → error（エラー画面）
   */
  const submit = useCallback(async () => {
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
        scrollToTop();
      } else if (response.status === 422 && result.errors) {
        const serverErrors: ValidationErrors = {};
        for (const [key, messages] of Object.entries(result.errors)) {
          serverErrors[key as keyof ContactFormData] = messages[0];
        }
        setErrors(serverErrors);
        setStep("input");
        scrollToTop();
      } else {
        setServerError(result.message);
        setStep("error");
        scrollToTop();
      }
    } catch {
      setServerError(
        "通信エラーが発生しました。しばらく経ってからお試しください。"
      );
      setStep("error");
      scrollToTop();
    } finally {
      setSubmitting(false);
    }
  }, [formData]);

  /** フォームを初期状態にリセットして最初に戻る */
  const reset = useCallback(() => {
    setFormData(initialFormData);
    setErrors({});
    setServerError("");
    setStep("input");
    scrollToTop();
  }, []);

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
