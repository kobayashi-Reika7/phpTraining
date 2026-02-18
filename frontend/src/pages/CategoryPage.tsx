import { Link, useParams } from "react-router";
import { useApi } from "../hooks/useApi";
import type { CategoryDemos } from "../types";

/**
 * カテゴリ詳細ページ（S-02）
 *
 * 選択したカテゴリに属するデモファイルをカード形式で一覧表示する。
 * useParams で URL パラメータ（カテゴリID）を取得する。
 */
export function CategoryPage() {
  const { id } = useParams<{ id: string }>();
  const { data, loading, error } = useApi<CategoryDemos>(
    `/api/categories/${id}/demos`
  );

  if (loading) return <div className="loading">読み込み中...</div>;
  if (error) return <div className="error">エラー: {error}</div>;
  if (!data) return <div className="error">カテゴリが見つかりません</div>;

  return (
    <div className="category-page">
      <h1>{data.category.name}</h1>

      <div className="demo-list">
        {data.demos.map((demo) => (
          <Link
            key={demo.filename}
            to={`/demo/${encodeURIComponent(demo.filename)}`}
            className={`demo-card ${!demo.runnable ? "demo-card-disabled" : ""}`}
          >
            <div className="demo-card-header">
              <span className="demo-card-icon">
                {demo.runnable ? "📄" : "⚠️"}
              </span>
              <code className="demo-card-filename">{demo.filename}</code>
            </div>
            <h3 className="demo-card-title">{demo.title}</h3>
            <p className="demo-card-theme">{demo.theme}</p>
            {!demo.runnable && (
              <span className="demo-card-badge">実行不可</span>
            )}
          </Link>
        ))}
      </div>
    </div>
  );
}
