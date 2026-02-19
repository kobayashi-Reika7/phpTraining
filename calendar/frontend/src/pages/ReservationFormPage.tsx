import { useState, useEffect, useCallback } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import DepartmentSelector from '../components/DepartmentSelector';
import TimeSlotGrid from '../components/TimeSlotGrid';
import { PURPOSES } from '../constants/masterData';
import { getSlots } from '../services/slotService';
import { formatDate, getWeekdayLabel, isJapaneseHoliday } from '../utils/holiday';
import type { AvailabilityResponse } from '../types/slot';

export default function ReservationFormPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const stateDate = (location.state as { date?: string })?.date;

  const [step, setStep] = useState(1);
  const [department, setDepartment] = useState('');
  const [purpose, setPurpose] = useState('first');
  const [selectedDate, setSelectedDate] = useState(stateDate || '');
  const [selectedTime, setSelectedTime] = useState<string | null>(null);
  const [availability, setAvailability] = useState<AvailabilityResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // 日付選択用: 今日から14日分の日付一覧
  const dateList = useCallback(() => {
    const dates: string[] = [];
    const today = new Date();
    for (let i = 0; i < 14; i++) {
      const d = new Date(today);
      d.setDate(today.getDate() + i);
      dates.push(formatDate(d));
    }
    return dates;
  }, []);

  useEffect(() => {
    if (!department || !selectedDate) {
      setAvailability(null);
      return;
    }

    let cancelled = false;
    setLoading(true);
    setError('');
    setSelectedTime(null);

    getSlots(department, selectedDate)
      .then(res => { if (!cancelled) setAvailability(res); })
      .catch(err => { if (!cancelled) setError(err.message || '空き枠の取得に失敗しました。'); })
      .finally(() => { if (!cancelled) setLoading(false); });

    return () => { cancelled = true; };
  }, [department, selectedDate]);

  const canConfirm = department && selectedDate && selectedTime && purpose;

  const handleConfirm = () => {
    if (!canConfirm) return;
    navigate('/reserve/confirm', {
      state: {
        department,
        date: selectedDate,
        time: selectedTime,
        purpose,
      },
    });
  };

  return (
    <div className="page reservation-form-page">
      <h1 className="page-title">予約する</h1>

      {/* Step indicator */}
      <div className="step-indicator">
        <span className={`step ${step >= 1 ? 'active' : ''}`}>1. 診療科</span>
        <span className={`step ${step >= 2 ? 'active' : ''}`}>2. 日時</span>
        <span className={`step ${step >= 3 ? 'active' : ''}`}>3. 確認</span>
      </div>

      {error && <div className="error-message">{error}</div>}

      {/* Step 1: 診療科 + 種別選択 */}
      {step === 1 && (
        <section className="form-section">
          <h2 className="form-section-title">診療科を選択</h2>
          <DepartmentSelector
            selected={department}
            onSelect={(dept) => { setDepartment(dept); }}
          />

          {department && (
            <>
              <h2 className="form-section-title mt-1">受診種別</h2>
              <div className="purpose-selector">
                {PURPOSES.map(p => (
                  <button
                    key={p.id}
                    className={`purpose-btn ${purpose === p.id ? 'active' : ''}`}
                    onClick={() => setPurpose(p.id)}
                  >
                    {p.label}
                  </button>
                ))}
              </div>
              <button className="btn btn-primary mt-1" onClick={() => setStep(2)}>
                日時選択へ
              </button>
            </>
          )}
        </section>
      )}

      {/* Step 2: 日付 + 時間枠選択 */}
      {step === 2 && (
        <section className="form-section">
          <button className="btn btn-text" onClick={() => setStep(1)}>
            ← 診療科の選択に戻る
          </button>

          <h2 className="form-section-title">日付を選択</h2>
          <div className="date-picker-list">
            {dateList().map(dateStr => {
              const d = new Date(dateStr + 'T00:00:00');
              const dayLabel = getWeekdayLabel(d);
              const holiday = isJapaneseHoliday(d);
              const isSun = d.getDay() === 0;
              const isSat = d.getDay() === 6;
              const isActive = dateStr === selectedDate;

              return (
                <button
                  key={dateStr}
                  className={[
                    'date-picker-item',
                    isActive ? 'active' : '',
                    holiday || isSun ? 'holiday' : '',
                    isSat ? 'saturday' : '',
                  ].filter(Boolean).join(' ')}
                  onClick={() => setSelectedDate(dateStr)}
                >
                  <span className="date-picker-day">{d.getDate()}</span>
                  <span className="date-picker-weekday">{dayLabel}</span>
                  {holiday && <span className="date-picker-badge">祝</span>}
                </button>
              );
            })}
          </div>

          {selectedDate && (
            <>
              <h2 className="form-section-title mt-1">
                {selectedDate.replace(/-/g, '/')} の空き状況
              </h2>
              {loading ? (
                <div className="loading">読み込み中...</div>
              ) : availability?.is_holiday ? (
                <div className="info-message">祝日のため予約できません。</div>
              ) : availability ? (
                <TimeSlotGrid
                  slots={availability.slots}
                  selectedTime={selectedTime}
                  onSelect={setSelectedTime}
                />
              ) : null}
            </>
          )}

          {selectedTime && (
            <div className="form-footer">
              <div className="form-summary">
                <span>{department}</span>
                <span>{selectedDate}</span>
                <span>{selectedTime}</span>
              </div>
              <button className="btn btn-primary" onClick={handleConfirm}>
                確認画面へ
              </button>
            </div>
          )}
        </section>
      )}
    </div>
  );
}
