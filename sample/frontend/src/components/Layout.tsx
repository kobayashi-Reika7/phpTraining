import { Outlet } from "react-router";
import { Sidebar } from "./Sidebar";

/**
 * 全体レイアウトコンポーネント
 *
 * ヘッダー + サイドバー + メインコンテンツの3カラム構成。
 * Outlet は React Router の機能で、現在のURLに対応する
 * ページコンポーネント（TopPage/CategoryPage/DemoPage）がここに挿入される。
 */
export function Layout() {
  return (
    <div className="app-layout">
      <Sidebar />
      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}
