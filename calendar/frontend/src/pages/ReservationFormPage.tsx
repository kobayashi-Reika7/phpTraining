import { useState, useEffect, useMemo } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import DepartmentSelector from '../components/DepartmentSelector';
import TimeSlotGrid from '../components/TimeSlotGrid';
import { PURPOSES } from '../constants/masterData';
import { getSlots } from '../services/slotService';
import { formatDate, getWeekdayLabel, isJapaneseHoliday } from '../utils/holiday';
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

function getWeekStart(base: Date): Date {
  const d = new Date(base);
  const day = d.getDay();
  d.setDate(d.getDate() - day);
  d.setHours(0, 0, 0, 0);
  return d;
}

function buildWeekDates(weekStart: Date): string[] {
  const dates: string[] = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(weekStart);
    d.setDate(weekStart.getDate() + i);
    dates.push(formatDate(d));
  }
  return dates;
}

export default function ReservationFormPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const state = (location.state as LocationState) || {};

  const isEdit = !!state.editMode;

  const [step, setStep] = useState(isEdit ? 2 : 1);
  const [department, setDepartment] = useState(state.department || '');
  const [purpose, setPurpose] = useState(
    state.purpose
      ? (PURPOSES.find(p => p.label === state.purpose)?.id || 'first')
      : 'first'
  );
  const [selectedDate, setSelectedDate] = useState(state.date || '');
  const [selectedTime, setSelectedTime] = useState<string | null>(state.time || null);
  const [availability, setAvailability] = useState<AvailabilityResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const todayStr = useMemo(() => formatDate(new Date()), []);
  const thisWeekStart = useMemo(() => getWeekStart(new Date()), []);

  const initialWeekStart = useMemo(() => {
    if (isEdit && state.date) {
      return getWeekStart(new Date(state.date + 'T00:00:00'));
    }
    return thisWeekStart;
  }, [isEdit, state.date, thisWeekStart]);

  const [weekStart, setWeekStart] = useState<Date>(initialWeekStart);

  const weekDates = useMemo(() => buildWeekDates(weekStart), [weekStart]);

  const canGoPrev = weekStart.getTime() > thisWeekStart.getTime();

  const goPrevWeek = () => {
    const prev = new Date(weekStart);
    prev.setDate(prev.getDate() - 7);
    if (prev.getTime() >= thisWeekStart.getTime()) {
      setWeekStart(prev);
    }
  };

  const goNextWeek = () => {
    const next = new Date(weekStart);
    next.setDate(next.getDate() + 7);
    setWeekStart(next);
  };

  const weekLabel = useMemo(() => {
    const first = new Date(weekDates[0] + 'T00:00:00');
    const last = new Date(weekDates[6] + 'T00:00:00');
    const m1 = first.getMonth() + 1;
    const m2 = last.getMonth() + 1;
    if (m1 === m2) {
      return `${first.getFullYear()}年${m1}月${first.getDate()}日〜${last.getDate()}日`;
    }
    return `${m1}/${first.getDate()}〜${m2}/${last.getDate()}`;
  }, [weekDates]);

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

  return (
    <div className="page reservation-form-page">
      <h1 className="page-title">{isEdit ? '予約を変更する' : '予約する'}</h1>

      {isEdit && (
        <div className="info-message">
          現在の予約: {state.department} / {state.date?.replace(/-/g, '/')} {state.time}
          {state.doctor && ` / 担当: ${state.doctor}`}
        </div>
      )}

      <div className="step-indicator">
        <span className={`step ${step >= 1 ? 'active' : ''}`}>1. 診療科</span>
        <span className={`step ${step >= 2 ? 'active' : ''}`}>2. 日時</span>
        <span className={`step ${step >= 3 ? 'active' : ''}`}>3. 確認</span>
      </div>

      {error && <div className="error-message">{error}</div>}

      {/* Step 1 */}
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

      {/* Step 2 */}
      {step === 2 && (
        <section className="form-section">
          <button className="btn btn-text" onClick={() => setStep(1)}>
            ← 診療科の選択に戻る
          </button>

          <h2 className="form-section-title">日付を選択</h2>

          {/* Week navigation */}
          <div className="week-nav">
            <button
              className="week-nav-btn"
              onClick={goPrevWeek}
              disabled={!canGoPrev}
              aria-label="前週"
            >
              ‹ 前週
            </button>
            <span className="week-nav-label">{weekLabel}</span>
            <button
              className="week-nav-btn"
              onClick={goNextWeek}
              aria-label="翌週"
            >
              翌週 ›
            </button>
          </div>

          {/* 7-day flat list */}
          <div className="date-picker-list">
            {weekDates.map(dateStr => {
              const d = new Date(dateStr + 'T00:00:00');
              const dayLabel = getWeekdayLabel(d);
              const holiday = isJapaneseHoliday(d);
              const isSun = d.getDay() === 0;
              const isSat = d.getDay() === 6;
              const isWeekend = isSat || isSun;
              const isPast = dateStr < todayStr;
              const isClosed = holiday || isWeekend || isPast;
              const isActive = dateStr === selectedDate;

              return (
                <button
                  key={dateStr}
                  className={[
                    'date-picker-item',
                    isActive ? 'active' : '',
                    isPast ? 'past' : '',
                    isClosed ? 'holiday' : '',
                    isSat ? 'saturday' : '',
                  ].filter(Boolean).join(' ')}
                  disabled={isClosed}
                  onClick={() => setSelectedDate(dateStr)}
                >
                  <span className="date-picker-month">{d.getMonth() + 1}月</span>
                  <span className="date-picker-day">{d.getDate()}</span>
                  <span className="date-picker-weekday">{dayLabel}</span>
                  {holiday && <span className="date-picker-badge">祝</span>}
                  {isWeekend && !holiday && <span className="date-picker-badge">休</span>}
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
                <span>{selectedDate.replace(/-/g, '/')}</span>
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
