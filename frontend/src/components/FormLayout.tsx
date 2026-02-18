import type { ReactNode } from "react";
import { StepIndicator } from "./StepIndicator";

/**
 * フォーム全体の共通レイアウトコンポーネント
 *
 * ヘッダー（タイトル + ステップインジケーター）、コンテナ、フッターを提供する。
 * 全ステップ共通の外枠なので、各ページは中身だけ実装すればよい。
 */
interface FormLayoutProps {
  title: string;
  children: ReactNode;
}

export function FormLayout({ title, children }: FormLayoutProps) {
  const isErrorStep = title === "エラー";

  return (
    <div className="form-layout">
      <header className="form-header">
        <h1>お問い合わせフォーム</h1>
        {!isErrorStep && <StepIndicator currentStep={title} />}
      </header>
      <main className="form-container">{children}</main>
      <footer className="form-footer">
        <p>Contact Form &mdash; React 19 + Laravel 12</p>
      </footer>
    </div>
  );
}
