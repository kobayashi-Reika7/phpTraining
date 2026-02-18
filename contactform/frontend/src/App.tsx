import { useContactForm } from "./hooks/useContactForm";
import { ErrorBoundary } from "./components/ErrorBoundary";
import { InputPage } from "./pages/InputPage";
import { ConfirmPage } from "./pages/ConfirmPage";
import { CompletePage } from "./pages/CompletePage";
import { ErrorPage } from "./pages/ErrorPage";
import "./App.css";

/**
 * アプリケーションのルートコンポーネント
 *
 * React Router を使わず、useState でステップ（step）を管理する SPA。
 * コンタクトフォームは「入力→確認→完了」の一本道なので、
 * URL を変えずに画面を切り替える方がシンプルで使いやすい。
 *
 * ErrorBoundary で全体をラップし、予期しないエラーでもアプリがクラッシュしないようにする。
 */
function ContactForm() {
  const {
    formData,
    errors,
    step,
    serverError,
    submitting,
    updateField,
    validateAndConfirm,
    goBackToInput,
    submit,
    reset,
  } = useContactForm();

  switch (step) {
    case "input":
      return (
        <InputPage
          formData={formData}
          errors={errors}
          updateField={updateField}
          validateAndConfirm={validateAndConfirm}
        />
      );
    case "confirm":
      return (
        <ConfirmPage
          formData={formData}
          goBackToInput={goBackToInput}
          submit={submit}
          submitting={submitting}
        />
      );
    case "complete":
      return <CompletePage reset={reset} />;
    case "error":
      return (
        <ErrorPage serverError={serverError} goBackToInput={goBackToInput} />
      );
  }
}

function App() {
  return (
    <ErrorBoundary>
      <ContactForm />
    </ErrorBoundary>
  );
}

export default App;
