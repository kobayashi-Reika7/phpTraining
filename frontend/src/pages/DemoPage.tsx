import { useState, useEffect } from "react";
import { Link, useParams } from "react-router";
import { useApi } from "../hooks/useApi";
import { CodeBlock } from "../components/CodeBlock";
import { ExecutionResult } from "../components/ExecutionResult";
import type { DemoDetail, DemoRunResult } from "../types";

/**
 * デモ詳細ページ（S-03）
 *
 * PHPファイルのソースコードをハイライト表示し、
 * 「実行する」ボタンで実際にPHPを実行して結果を表示する。
 */
export function DemoPage() {
  const { filename } = useParams<{ filename: string }>();
  const decodedFilename = decodeURIComponent(filename ?? "");

  const { data: demo, loading, error } = useApi<DemoDetail>(
    `/api/demos/${decodedFilename}`
  );

  useEffect(() => {
    if (demo) document.title = `${demo.title} | PHP Omoshiroi Viewer`;
  }, [demo]);

  const [runResult, setRunResult] = useState<DemoRunResult | null>(null);
  const [running, setRunning] = useState(false);

  /** 「実行する」ボタンが押された時の処理 */
  const handleRun = async () => {
    setRunning(true);
    try {
      const res = await fetch(`/api/demos/${decodedFilename}/run`, {
        method: "POST",
      });
      const json = await res.json();
      setRunResult(json.data);
    } catch {
      setRunResult({
        filename: decodedFilename,
        stdout: "",
        stderr: "実行に失敗しました",
        exit_code: -1,
        executed_at: new Date().toISOString(),
      });
    } finally {
      setRunning(false);
    }
  };

  if (loading) return <div className="loading"><span className="spinner" />読み込み中...</div>;
  if (error) return <div className="error">エラー: {error}</div>;
  if (!demo) return <div className="error">デモが見つかりません</div>;

  return (
    <div className="demo-page">
      <Link to={`/category/${demo.category.id}`} className="breadcrumb">
        ← {demo.category.name} に戻る
      </Link>

      <h1>{demo.title}</h1>
      <div className="demo-meta">
        <code className="demo-filename">{demo.filename}</code>
        <span className="demo-theme-badge">{demo.theme}</span>
      </div>
      <p className="demo-description">{demo.description}</p>

      <CodeBlock code={demo.code} />

      <div className="demo-actions">
        {demo.runnable ? (
          <button
            className="run-button"
            onClick={handleRun}
            disabled={running}
          >
            {running ? "⏳ 実行中..." : "▶ 実行する"}
          </button>
        ) : (
          <div className="run-disabled">
            ⚠ このファイルは実行できません（構文エラーまたは依存不足）
          </div>
        )}
      </div>

      {runResult && (
        <ExecutionResult
          stdout={runResult.stdout}
          stderr={runResult.stderr}
          exitCode={runResult.exit_code}
          loading={running}
        />
      )}
    </div>
  );
}
