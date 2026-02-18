import { NavLink } from "react-router";
import { useApi } from "../hooks/useApi";
import type { Category } from "../types";

/**
 * サイドバーコンポーネント
 *
 * 全画面で共通表示されるカテゴリナビゲーション。
 * NavLink を使うことで、現在選択中のカテゴリにハイライトが付く。
 */
export function Sidebar() {
  const { data: categories, loading } = useApi<Category[]>("/api/categories");

  return (
    <aside className="sidebar">
      <nav>
        <NavLink to="/" className="sidebar-title" end>
          🐘 PHP Omoshiroi Viewer
        </NavLink>

        <div className="sidebar-section">カテゴリ</div>

        {loading && <div className="sidebar-loading">読み込み中...</div>}

        {categories?.map((cat) => (
          <NavLink
            key={cat.id}
            to={`/category/${cat.id}`}
            className={({ isActive }) =>
              `sidebar-link ${isActive ? "active" : ""}`
            }
          >
            <span className="sidebar-link-name">{cat.name}</span>
            <span className="sidebar-link-count">{cat.demo_count}</span>
          </NavLink>
        ))}
      </nav>
    </aside>
  );
}
