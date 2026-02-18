import type { ReactNode } from "react";

/**
 * フォーム全体の共通レイアウトコンポーネント
 *
 * ヘッダー（タイトル）、ステップインジケーター、コンテナ、フッターを提供する。
 * 全ステップ共通の外枠なので、各ページは中身だけ実装すればよい。
 *
 * ステップインジケーターにより、ユーザーは「今どこにいて、あと何ステップか」が分かる。
 */
interface FormLayoutProps {
  title: string;
  children: ReactNode;
}

const STEPS = ["入力", "確認", "完了"] as const;

export function FormLayout({ title, children }: FormLayoutProps) {
  const isErrorStep = title === "エラー";

  return (
    <div className="form-layout">
      <header className="form-header">
        <h1>お問い合わせフォーム</h1>
        {!isErrorStep && (
          <div className="step-indicator">
            {STEPS.map((stepName, index) => (
              <div
                key={stepName}
                className={`step-item ${title === stepName ? "step-active" : ""} ${
                  STEPS.indexOf(title as (typeof STEPS)[number]) > index
                    ? "step-done"
                    : ""
                }`}
              >
                <span className="step-number">{index + 1}</span>
                <span className="step-name">{stepName}</span>
              </div>
            ))}
          </div>
        )}
      </header>
      <main className="form-container">{children}</main>
      <footer className="form-footer">
        <p>Contact Form &mdash; React 19 + Laravel 12</p>
      </footer>
    </div>
  );
}
