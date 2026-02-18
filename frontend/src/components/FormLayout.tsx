import type { ReactNode } from "react";

/**
 * フォーム全体の共通レイアウトコンポーネント
 *
 * ヘッダー（タイトル）とコンテナを提供する。
 * 全ステップ共通の外枠なので、各ページは中身だけ実装すればよい。
 *
 * Props:
 * - title: 現在のステップ名（例: "入力", "確認", "完了"）
 * - children: 各ページのコンテンツ
 */
interface FormLayoutProps {
  title: string;
  children: ReactNode;
}

export function FormLayout({ title, children }: FormLayoutProps) {
  return (
    <div className="form-layout">
      <header className="form-header">
        <h1>お問い合わせフォーム</h1>
        <p className="form-step-label">{title}</p>
      </header>
      <main className="form-container">{children}</main>
      <footer className="form-footer">
        <p>Contact Form &mdash; React 19 + Laravel 12</p>
      </footer>
    </div>
  );
}
