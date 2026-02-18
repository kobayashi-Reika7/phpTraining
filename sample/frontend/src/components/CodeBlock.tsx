import { useEffect, useRef } from "react";
import hljs from "highlight.js/lib/core";
import php from "highlight.js/lib/languages/php";
import "highlight.js/styles/github-dark.css";

// PHP言語のハイライト定義を登録
hljs.registerLanguage("php", php);

/**
 * シンタックスハイライト付きコード表示コンポーネント
 *
 * highlight.js を使って PHP コードを色分け表示する。
 * useRef + useEffect で DOM に直接ハイライトを適用している。
 */
interface CodeBlockProps {
  code: string;
  language?: string;
}

export function CodeBlock({ code, language = "php" }: CodeBlockProps) {
  const codeRef = useRef<HTMLElement>(null);

  useEffect(() => {
    if (codeRef.current) {
      codeRef.current.removeAttribute("data-highlighted");
      hljs.highlightElement(codeRef.current);
    }
  }, [code]);

  return (
    <div className="code-block">
      <div className="code-block-header">📄 ソースコード</div>
      <pre>
        <code ref={codeRef} className={`language-${language}`}>
          {code}
        </code>
      </pre>
    </div>
  );
}
