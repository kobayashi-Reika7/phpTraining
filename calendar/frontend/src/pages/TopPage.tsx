import { Link } from 'react-router-dom';
import { CATEGORIES, DEPARTMENTS_BY_CATEGORY } from '../constants/masterData';
import { useAuth } from '../hooks/useAuth';

export default function TopPage() {
  const { user } = useAuth();

  return (
    <div className="page top-page">
      <section className="hero">
        <h1 className="hero-title">予約カレンダー</h1>
        <p className="hero-subtitle">
          Web から簡単に診療予約ができます
        </p>
        {user ? (
          <Link to="/menu" className="btn btn-primary btn-lg">
            メニューへ
          </Link>
        ) : (
          <Link to="/login" className="btn btn-primary btn-lg">
            Web 予約はこちら
          </Link>
        )}
      </section>

      <section className="info-section">
        <h2 className="section-title">診療科のご案内</h2>
        <div className="department-cards">
          {CATEGORIES.map(cat => (
            <div key={cat.id} className="info-card">
              <h3 className="info-card-title">{cat.label}</h3>
              <ul className="info-card-list">
                {DEPARTMENTS_BY_CATEGORY[cat.id]?.map(dept => (
                  <li key={dept.id}>{dept.label}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </section>

      <section className="info-section">
        <h2 className="section-title">診療時間</h2>
        <div className="info-card">
          <table className="hours-table">
            <thead>
              <tr>
                <th>区分</th><th>時間</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>午前</td><td>09:00〜12:00</td></tr>
              <tr><td>午後</td><td>13:00〜17:00</td></tr>
            </tbody>
          </table>
          <p className="info-note">※ 土日・祝日は休診です</p>
        </div>
      </section>
    </div>
  );
}
