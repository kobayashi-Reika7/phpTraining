import { useState, useEffect } from "react";

/**
 * GET リクエストの共通カスタムフック
 *
 * 全画面で「ローディング中→データ取得→エラー」のパターンが共通なので、
 * この処理を1つのフックにまとめている。
 * 使い方: const { data, loading, error } = useApi<Category[]>("/api/categories");
 *
 * @param url - API のエンドポイント（例: "/api/categories"）
 */
export function useApi<T>(url: string) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);

    fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then((json) => setData(json.data))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [url]);

  return { data, loading, error };
}
