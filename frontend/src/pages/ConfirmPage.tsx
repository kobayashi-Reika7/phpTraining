import { FormLayout } from "../components/FormLayout";
import type { ContactFormData } from "../types";

/**
 * 確認画面コンポーネント
 *
 * 入力内容を一覧表示し、ユーザーに最終確認してもらうステップ。
 * 「修正する」で入力画面に戻り、「送信する」で API にリクエストを送る。
 */
interface ConfirmPageProps {
  formData: ContactFormData;
  goBackToInput: () => void;
  submit: () => void;
  submitting: boolean;
}

export function ConfirmPage({
  formData,
  goBackToInput,
  submit,
  submitting,
}: ConfirmPageProps) {
  return (
    <FormLayout title="確認">
      <p className="confirm-message">以下の内容でよろしいですか？</p>

      <dl className="confirm-list">
        <div className="confirm-item">
          <dt>名前</dt>
          <dd>{formData.name}</dd>
        </div>
        <div className="confirm-item">
          <dt>メールアドレス</dt>
          <dd>{formData.email}</dd>
        </div>
        <div className="confirm-item">
          <dt>性別</dt>
          <dd>{formData.gender || "未選択"}</dd>
        </div>
        <div className="confirm-item">
          <dt>問い合わせの種類</dt>
          <dd>{formData.kind || "未選択"}</dd>
        </div>
        <div className="confirm-item">
          <dt>使用プログラミング言語</dt>
          <dd>{formData.lang.length > 0 ? formData.lang.join(", ") : "未選択"}</dd>
        </div>
        <div className="confirm-item">
          <dt>コメント</dt>
          <dd className="confirm-comment">{formData.comment}</dd>
        </div>
      </dl>

      <div className="form-actions">
        <button
          type="button"
          className="btn btn-secondary"
          onClick={goBackToInput}
          disabled={submitting}
        >
          修正する
        </button>
        <button
          type="button"
          className="btn btn-primary"
          onClick={submit}
          disabled={submitting}
        >
          {submitting ? "送信中..." : "送信する"}
        </button>
      </div>
    </FormLayout>
  );
}
