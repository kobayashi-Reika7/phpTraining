import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getReservations, cancelReservation } from '../services/reservationService';
import { getWeekdayLabel } from '../utils/holiday';
import type { Reservation } from '../types/reservation';

export default function MyReservationsPage() {
  const [reservations, setReservations] = useState<Reservation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [cancelling, setCancelling] = useState<number | null>(null);
  const navigate = useNavigate();

  const today = new Date().toISOString().slice(0, 10);

  useEffect(() => {
    getReservations()
      .then(setReservations)
      .catch(err => setError(err.message || '予約の取得に失敗しました。'))
      .finally(() => setLoading(false));
  }, []);

  const upcoming = reservations.filter(r => r.date >= today);
  const past = reservations.filter(r => r.date < today);

  const handleCancel = async (id: number) => {
    if (!confirm('この予約をキャンセルしますか？')) return;

    setCancelling(id);
    try {
      await cancelReservation(id);
      setReservations(prev => prev.filter(r => r.id !== id));
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'キャンセルに失敗しました。';
      setError(msg);
    } finally {
      setCancelling(null);
    }
  };

  const handleChange = (reservation: Reservation) => {
    navigate('/reserve/form', {
      state: {
        editMode: true,
        reservationId: reservation.id,
        department: reservation.department,
        date: reservation.date,
        time: reservation.time,
        purpose: reservation.purpose,
        doctor: reservation.doctor,
      },
    });
  };

  const renderList = (items: Reservation[], label: string, showActions: boolean) => (
    <section className="reservation-section">
      <h2 className="section-title">{label}（{items.length}件）</h2>
      {items.length === 0 ? (
        <p className="empty-message">{label}はありません。</p>
      ) : (
        <div className="reservation-list">
          {items.map(r => {
            const d = new Date(r.date + 'T00:00:00');
            return (
              <div key={r.id} className="reservation-card">
                <div className="reservation-info">
                  <div className="reservation-date">
                    {r.date.replace(/-/g, '/')}（{getWeekdayLabel(d)}）{r.time}
                  </div>
                  <div className="reservation-dept">{r.department}</div>
                  {r.doctor && <div className="reservation-doctor">担当: {r.doctor}</div>}
                  {r.purpose && <div className="reservation-purpose">{r.purpose}</div>}
                </div>
                {showActions && (
                  <div className="reservation-actions">
                    <button
                      className="btn btn-sm btn-outline"
                      onClick={() => handleChange(r)}
                    >
                      変更
                    </button>
                    <button
                      className="btn btn-sm btn-danger"
                      onClick={() => handleCancel(r.id)}
                      disabled={cancelling === r.id}
                    >
                      {cancelling === r.id ? '...' : 'キャンセル'}
                    </button>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </section>
  );

  return (
    <div className="page reservations-page">
      <h1 className="page-title">予約一覧</h1>

      {error && <div className="error-message">{error}</div>}

      {loading ? (
        <div className="loading">読み込み中...</div>
      ) : (
        <>
          {renderList(upcoming, '今後の予約', true)}
          {renderList(past, '過去の予約', false)}
        </>
      )}

      <button className="btn btn-outline mt-1" onClick={() => navigate('/menu')}>
        メニューに戻る
      </button>
    </div>
  );
}
