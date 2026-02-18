import { memo } from "react";
import { FormLayout } from "../components/FormLayout";

/**
 * 完了画面コンポーネント
 *
 * メール送信成功後に表示されるステップ。
 * 「最初に戻る」でフォームをリセットし、再度入力できるようにする。
 */
interface CompletePageProps {
  reset: () => void;
}

export const CompletePage = memo(function CompletePage({ reset }: CompletePageProps) {
  return (
    <FormLayout title="完了">
      <div className="complete-content">
        <div className="complete-icon">&#x2714;</div>
        <h2>送信が完了しました</h2>
        <p>お問い合わせありがとうございます。</p>
        <p>確認メールをお送りしましたので、ご確認ください。</p>
        <div className="form-actions">
          <button type="button" className="btn btn-primary" onClick={reset}>
            最初に戻る
          </button>
        </div>
      </div>
    </FormLayout>
  );
});
