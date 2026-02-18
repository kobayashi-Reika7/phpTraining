import { Component } from "react";
import type { ErrorInfo, ReactNode } from "react";

/**
 * エラーバウンダリ
 *
 * React のコンポーネントツリー内で発生した JavaScript エラーをキャッチし、
 * アプリ全体がクラッシュする代わりにフォールバック UI を表示する。
 *
 * クラスコンポーネントでしか実装できない（React 19 時点）。
 * componentDidCatch() でエラー情報をログに記録する。
 */
interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
}

interface ErrorBoundaryProps {
  children: ReactNode;
}

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error("ErrorBoundary caught:", error, info.componentStack);
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null });
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="form-layout">
          <header className="form-header">
            <h1>お問い合わせフォーム</h1>
          </header>
          <main className="form-container">
            <div className="error-content">
              <div className="error-icon">&#x26A0;</div>
              <h2>予期しないエラーが発生しました</h2>
              <p className="error-message">
                {this.state.error?.message || "不明なエラー"}
              </p>
              <div className="form-actions">
                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={this.handleReset}
                >
                  最初からやり直す
                </button>
              </div>
            </div>
          </main>
        </div>
      );
    }

    return this.props.children;
  }
}
