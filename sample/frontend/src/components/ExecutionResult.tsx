/**
 * 実行結果表示コンポーネント
 *
 * stdout と stderr を色分けして表示する。
 * stderr がある場合は警告色（オレンジ）で表示し、視覚的に区別できるようにしている。
 */
interface ExecutionResultProps {
  stdout: string;
  stderr: string;
  exitCode: number;
  loading?: boolean;
}

export function ExecutionResult({
  stdout,
  stderr,
  exitCode,
  loading,
}: ExecutionResultProps) {
  if (loading) {
    return (
      <div className="execution-result">
        <div className="execution-result-header">📟 実行結果</div>
        <div className="execution-result-loading">実行中...</div>
      </div>
    );
  }

  return (
    <div className="execution-result">
      <div className="execution-result-header">
        📟 実行結果
        <span
          className={`exit-code ${exitCode === 0 ? "exit-code-ok" : "exit-code-error"}`}
        >
          exit: {exitCode}
        </span>
      </div>

      {stderr && (
        <pre className="execution-stderr">
          <div className="execution-stderr-label">⚠ stderr</div>
          {stderr}
        </pre>
      )}

      {stdout && (
        <pre className="execution-stdout">
          <div className="execution-stdout-label">stdout</div>
          {stdout}
        </pre>
      )}

      {!stdout && !stderr && (
        <div className="execution-empty">出力なし</div>
      )}
    </div>
  );
}
