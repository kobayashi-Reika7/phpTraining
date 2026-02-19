import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { createReservation } from '../services/reservationService';
import { PURPOSES } from '../constants/masterData';
import { getWeekdayLabel } from '../utils/holiday';

interface ConfirmState {
  department: string;
  date: string;
  time: string;
  purpose: string;
}

export default function ReserveConfirmPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const state = location.state as ConfirmState | null;

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  if (!state) {
    return (
      <div className="page">
        <p>予約情報がありません。</p>
        <button className="btn btn-primary" onClick={() => navigate('/reserve/form')}>
          予約フォームへ
        </button>
      </div>
    );
  }

  const d = new Date(state.date + 'T00:00:00');
  const purposeLabel = PURPOSES.find(p => p.id === state.purpose)?.label || state.purpose;

  const handleConfirm = async () => {
    setError('');
    setLoading(true);
    try {
      await createReservation({
        department: state.department,
        date: state.date,
        time: state.time,
        purpose: purposeLabel,
      });
      setSuccess(true);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : '予約に失敗しました。';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="page confirm-page">
        <div className="confirm-card success-card">
          <h1 className="confirm-title">予約が完了しました</h1>
          <p className="success-message">ご予約ありがとうございます。</p>
          <div className="confirm-actions">
            <button className="btn btn-primary" onClick={() => navigate('/reservations')}>
              予約一覧を見る
            </button>
            <button className="btn btn-outline" onClick={() => navigate('/menu')}>
              メニューに戻る
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="page confirm-page">
      <h1 className="page-title">予約内容の確認</h1>

      {error && <div className="error-message">{error}</div>}

      <div className="confirm-card">
        <dl className="confirm-details">
          <div className="confirm-row">
            <dt>診療科</dt>
            <dd>{state.department}</dd>
          </div>
          <div className="confirm-row">
            <dt>受診種別</dt>
            <dd>{purposeLabel}</dd>
          </div>
          <div className="confirm-row">
            <dt>日付</dt>
            <dd>{state.date.replace(/-/g, '/')}（{getWeekdayLabel(d)}）</dd>
          </div>
          <div className="confirm-row">
            <dt>時間</dt>
            <dd>{state.time}</dd>
          </div>
        </dl>

        <div className="confirm-actions">
          <button
            className="btn btn-primary btn-lg"
            onClick={handleConfirm}
            disabled={loading}
          >
            {loading ? '予約中...' : '予約を確定する'}
          </button>
          <button
            className="btn btn-outline"
            onClick={() => navigate(-1)}
            disabled={loading}
          >
            戻る
          </button>
        </div>
      </div>
    </div>
  );
}
