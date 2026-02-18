/**
 * ページ上部にスムーズスクロールする
 *
 * ステップ切替時やエラー発生時に呼び出し、
 * ユーザーがコンテンツの先頭を見られるようにする。
 */
export function scrollToTop(): void {
  window.scrollTo({ top: 0, behavior: "smooth" });
}
