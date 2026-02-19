import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import * as authService from '../services/authService';

export default function MenuPage() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await authService.logout();
    } catch {
      // ignore
    }
    logout();
    navigate('/');
  };

  return (
    <div className="page menu-page">
      <h1 className="page-title">メニュー</h1>
      <p className="menu-greeting">
        {user?.name} さん、こんにちは
      </p>

      <div className="menu-cards">
        <Link to="/reserve/form" className="menu-card">
          <span className="menu-card-icon">📅</span>
          <span className="menu-card-label">予約する</span>
          <span className="menu-card-desc">診療科・日時を選んで予約</span>
        </Link>

        <Link to="/reservations" className="menu-card">
          <span className="menu-card-icon">📋</span>
          <span className="menu-card-label">予約を確認する</span>
          <span className="menu-card-desc">予約一覧・変更・キャンセル</span>
        </Link>
      </div>

      <button className="btn btn-outline logout-btn" onClick={handleLogout}>
        ログアウト
      </button>
    </div>
  );
}
