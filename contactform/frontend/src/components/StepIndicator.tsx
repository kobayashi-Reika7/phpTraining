import { memo } from "react";

/**
 * ステップインジケーター
 *
 * 「1 入力 → 2 確認 → 3 完了」のように現在のステップを視覚化する。
 * memo() でラップすることで、currentStep が変わらない限り再レンダリングしない。
 */

const STEPS = ["入力", "確認", "完了"] as const;

interface StepIndicatorProps {
  currentStep: string;
}

export const StepIndicator = memo(function StepIndicator({
  currentStep,
}: StepIndicatorProps) {
  const currentIndex = STEPS.indexOf(
    currentStep as (typeof STEPS)[number]
  );

  return (
    <div className="step-indicator">
      {STEPS.map((stepName, index) => (
        <div
          key={stepName}
          className={`step-item ${
            currentStep === stepName ? "step-active" : ""
          } ${currentIndex > index ? "step-done" : ""}`}
        >
          <span className="step-number">{index + 1}</span>
          <span className="step-name">{stepName}</span>
        </div>
      ))}
    </div>
  );
});
