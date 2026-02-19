import { useMemo } from 'react';
import { isJapaneseHoliday, formatDate } from '../utils/holiday';

interface CalendarProps {
  year: number;
  month: number;
  selectedDate: string | null;
  reservedDates?: Set<string>;
  onSelectDate: (dateStr: string) => void;
  onMonthChange: (year: number, month: number) => void;
}

const WEEKDAY_HEADERS = ['日', '月', '火', '水', '木', '金', '土'];

export default function Calendar({
  year,
  month,
  selectedDate,
  reservedDates,
  onSelectDate,
  onMonthChange,
}: CalendarProps) {
  const today = useMemo(() => formatDate(new Date()), []);

  const weeks = useMemo(() => {
    const firstDay = new Date(year, month - 1, 1);
    const lastDay = new Date(year, month, 0);
    const startPad = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const cells: (Date | null)[] = [];
    for (let i = 0; i < startPad; i++) cells.push(null);
    for (let d = 1; d <= totalDays; d++) cells.push(new Date(year, month - 1, d));
    while (cells.length % 7 !== 0) cells.push(null);

    const rows: (Date | null)[][] = [];
    for (let i = 0; i < cells.length; i += 7) {
      rows.push(cells.slice(i, i + 7));
    }
    return rows;
  }, [year, month]);

  const goPrev = () => {
    if (month === 1) onMonthChange(year - 1, 12);
    else onMonthChange(year, month - 1);
  };

  const goNext = () => {
    if (month === 12) onMonthChange(year + 1, 1);
    else onMonthChange(year, month + 1);
  };

  return (
    <div className="calendar">
      <div className="calendar-header">
        <button className="calendar-nav" onClick={goPrev} aria-label="前月">&lt;</button>
        <span className="calendar-title">{year}年{month}月</span>
        <button className="calendar-nav" onClick={goNext} aria-label="翌月">&gt;</button>
      </div>

      <div className="calendar-grid">
        {WEEKDAY_HEADERS.map((w, i) => (
          <div
            key={w}
            className={`calendar-weekday ${i === 0 ? 'sunday' : ''} ${i === 6 ? 'saturday' : ''}`}
          >
            {w}
          </div>
        ))}

        {weeks.flat().map((date, idx) => {
          if (!date) return <div key={`empty-${idx}`} className="calendar-cell empty" />;

          const dateStr = formatDate(date);
          const isPast = dateStr < today;
          const isHoliday = isJapaneseHoliday(date);
          const isSunday = date.getDay() === 0;
          const isSaturday = date.getDay() === 6;
          const isSelected = dateStr === selectedDate;
          const isToday = dateStr === today;
          const hasReservation = reservedDates?.has(dateStr);

          const isWeekend = isSunday || isSaturday;
          const isClosed = isHoliday || isWeekend;
          const disabled = isPast || isClosed;

          const classes = [
            'calendar-cell',
            disabled ? 'disabled' : '',
            isSelected ? 'selected' : '',
            isToday ? 'today' : '',
            isSunday || isHoliday ? 'holiday' : '',
            isSaturday ? 'saturday' : '',
          ].filter(Boolean).join(' ');

          return (
            <button
              key={dateStr}
              className={classes}
              disabled={disabled}
              onClick={() => onSelectDate(dateStr)}
            >
              <span className="calendar-day">{date.getDate()}</span>
              {isHoliday && <span className="calendar-badge holiday-badge">祝</span>}
              {hasReservation && <span className="calendar-badge reservation-badge">予約</span>}
            </button>
          );
        })}
      </div>
    </div>
  );
}
