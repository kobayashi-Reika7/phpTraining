import { BrowserRouter, Routes, Route } from "react-router";
import { Layout } from "./components/Layout";
import { TopPage } from "./pages/TopPage";
import { CategoryPage } from "./pages/CategoryPage";
import { DemoPage } from "./pages/DemoPage";
import "./App.css";

/**
 * アプリケーションのルートコンポーネント
 *
 * BrowserRouter で SPA のルーティングを管理する。
 * Layout の中に Outlet があり、URLに応じて TopPage / CategoryPage / DemoPage が表示される。
 */
function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<TopPage />} />
          <Route path="/category/:id" element={<CategoryPage />} />
          <Route path="/demo/:filename" element={<DemoPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

export default App;
