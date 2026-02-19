import { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import DepartmentSelector from '../components/DepartmentSelector';
import Calendar from '../components/Calendar';
import TimeSlotGrid from '../components/TimeSlotGrid';
import { PURPOSES } from '../constants/masterData';
import { getSlots } from '../services/slotService';
import type { AvailabilityResponse } from '../types/slot';

interface LocationState {
  date?: string;
  editMode?: boolean;
  reservationId?: number;
  department?: string;
  time?: string;
  purpose?: string;
  doctor?: string;
}

export default function ReservationFormPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const state = (location.state as LocationState) || {};

  const isEdit = !!state.editMode;

  const now = new Date();
  const initYear = state.date ? parseInt(state.date.slice(0, 4), 10) : now.getFullYear();
  const initMonth = state.date ? parseInt(state.date.slice(5, 7), 10) : now.getMonth() + 1;

  const [step, setStep] = useState(isEdit ? 2 : 1);
  const [department, setDepartment] = useState(state.department || '');
  const [purpose, setPurpose] = useState(
    state.purpose
      ? (PURPOSES.find(p => p.label === state.purpose)?.id || 'first')
      : 'first'
  );
  const [calYear, setCalYear] = useState(initYear);
  const [calMonth, setCalMonth] = useState(initMonth);
  const [selectedDate, setSelectedDate] = useState(state.date || '');
  const [selectedTime, setSelectedTime] = useState<string | null>(state.time || null);
  const [availability, setAvailability] = useState<AvailabilityResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!department || !selectedDate) {
      setAvailability(null);
      return;
    }

    let cancelled = false;
    setLoading(true);
    setError('');
    if (!isEdit || availability !== null) {
      setSelectedTime(null);
    }

    getSlots(department, selectedDate)
      .then(res => { if (!cancelled) setAvailability(res); })
      .catch(err => { if (!cancelled) setError(err.message || '空き枠の取得に失敗しました。'); })
      .finally(() => { if (!cancelled) setLoading(false); });

    return () => { cancelled = true; };
  // eslint-disable-next-line react-hooks/exhaustive-deps
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
        ...(isEdit ? { editMode: true, reservationId: state.reservationId } : {}),
      },
    });
  };

  const handleMonthChange = (y: number, m: number) => {
    setCalYear(y);
    setCalMonth(m);
  };

  return (
    <div className="page reservation-form-page">
      <h1 className="page-title">{isEdit ? '予約を変更する' : '予約する'}</h1>

      {isEdit && (
        <div className="info-message">
          現在の予約: {state.department} / {state.date?.replace(/-/g, '/')} {state.time}
          {state.doctor && ` / 担当: ${state.doctor}`}
        </div>
      )}

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

      {/* Step 2: カレンダーで日付選択 + 時間枠選択 */}
      {step === 2 && (
        <section className="form-section">
          <button className="btn btn-text" onClick={() => setStep(1)}>
            ← 診療科の選択に戻る
          </button>

          <h2 className="form-section-title">日付を選択</h2>
          <Calendar
            year={calYear}
            month={calMonth}
            selectedDate={selectedDate || null}
            onSelectDate={setSelectedDate}
            onMonthChange={handleMonthChange}
          />

          {selectedDate && (
            <>
              <h2 className="form-section-title mt-1">
                {selectedDate.replace(/-/g, '/')} の空き状況
              </h2>
              {loading ? (
                <div className="loading">読み込み中...</div>
              ) : availability?.is_weekend ? (
                <div className="info-message">土日のため予約できません。</div>
              ) : availability?.is_holiday ? (
                <div className="info-message">祝日のため予約できません。</div>
              ) : availability ? (
                <TimeSlotGrid
                  slots={availability.slots}
                  selectedTime={selectedTime}
                  onSelect={setSelectedTime}
                  date={selectedDate}
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
