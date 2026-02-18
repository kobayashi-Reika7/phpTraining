import { memo } from "react";
import { FormLayout } from "../components/FormLayout";

/**
 * エラー画面コンポーネント
 *
 * サーバーエラー（500 等）や通信エラー時に表示されるステップ。
 * エラーメッセージを表示し、「入力画面に戻る」で再入力を促す。
 */
interface ErrorPageProps {
  serverError: string;
  goBackToInput: () => void;
}

export const ErrorPage = memo(function ErrorPage({ serverError, goBackToInput }: ErrorPageProps) {
  return (
    <FormLayout title="エラー">
      <div className="error-content">
        <div className="error-icon">&#x26A0;</div>
        <h2>送信に失敗しました</h2>
        <p className="error-message">{serverError}</p>
        <div className="form-actions">
          <button
            type="button"
            className="btn btn-secondary"
            onClick={goBackToInput}
          >
            入力画面に戻る
          </button>
        </div>
      </div>
    </FormLayout>
  );
});
