import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Calendar from '../components/Calendar';
import { getReservations } from '../services/reservationService';

export default function CalendarPage() {
  const navigate = useNavigate();
  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [reservedDates, setReservedDates] = useState<Set<string>>(new Set());

  useEffect(() => {
    getReservations()
      .then(res => setReservedDates(new Set(res.map(r => r.date))))
      .catch(() => {});
  }, []);

  const handleSelectDate = (dateStr: string) => {
    setSelectedDate(dateStr);
    navigate('/reserve/form', { state: { date: dateStr } });
  };

  return (
    <div className="page calendar-page">
      <h1 className="page-title">カレンダー</h1>
      <p className="page-desc">日付を選択して予約へ進めます。</p>

      <Calendar
        year={year}
        month={month}
        selectedDate={selectedDate}
        reservedDates={reservedDates}
        onSelectDate={handleSelectDate}
        onMonthChange={(y, m) => { setYear(y); setMonth(m); }}
      />

      <button className="btn btn-outline mt-1" onClick={() => navigate('/menu')}>
        メニューに戻る
      </button>
    </div>
  );
}
