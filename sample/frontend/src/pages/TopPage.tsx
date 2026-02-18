import { useEffect } from "react";
import { Link } from "react-router";
import { useApi } from "../hooks/useApi";
import type { Category } from "../types";

/**
 * トップページ（S-01）
 *
 * カテゴリカードを並べて表示する。
 * カードをクリックするとカテゴリ詳細ページに遷移する。
 */
export function TopPage() {
  const { data: categories, loading, error } = useApi<Category[]>("/api/categories");

  useEffect(() => {
    document.title = "PHP Omoshiroi Viewer";
  }, []);

  if (loading) return <div className="loading"><span className="spinner" />読み込み中...</div>;
  if (error) return <div className="error">エラー: {error}</div>;

  return (
    <div className="top-page">
      <h1>PHP面白コード集</h1>
      <p className="top-description">
        PHPの面白い仕様をインタラクティブに確認できるビューアです。
        <br />
        カテゴリを選んで、各デモのコードと実行結果を見てみましょう。
      </p>

      <div className="category-grid">
        {categories?.map((cat) => (
          <Link
            key={cat.id}
            to={`/category/${cat.id}`}
            className="category-card"
          >
            <div className="category-card-icon">{getCategoryIcon(cat.id)}</div>
            <div className="category-card-body">
              <h2>{cat.name}</h2>
              <p>{cat.description}</p>
              <span className="category-card-count">{cat.demo_count} 件</span>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}

/** カテゴリ ID に対応するアイコンを返す */
function getCategoryIcon(id: string): string {
  const icons: Record<string, string> = {
    array: "📦",
    "type-comparison": "⚖️",
    function: "🔧",
    "class-object": "🏗️",
    datetime: "📅",
    "string-regex": "🔤",
    other: "📎",
  };
  return icons[id] ?? "📄";
}
