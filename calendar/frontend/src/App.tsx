import { useState, useEffect, useCallback, Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route, Link, useNavigate } from 'react-router-dom';
import { AuthContext } from './hooks/useAuth';
import { useAuth } from './hooks/useAuth';
import ProtectedRoute from './components/ProtectedRoute';
import type { User } from './types/auth';
import * as authService from './services/authService';
import './App.css';

const TopPage = lazy(() => import('./pages/TopPage'));
const LoginPage = lazy(() => import('./pages/LoginPage'));
const SignupPage = lazy(() => import('./pages/SignupPage'));
const MenuPage = lazy(() => import('./pages/MenuPage'));
const CalendarPage = lazy(() => import('./pages/CalendarPage'));
const ReservationFormPage = lazy(() => import('./pages/ReservationFormPage'));
const ReserveConfirmPage = lazy(() => import('./pages/ReserveConfirmPage'));
const MyReservationsPage = lazy(() => import('./pages/MyReservationsPage'));

function Header() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try { await authService.logout(); } catch { /* ignore */ }
    logout();
    navigate('/');
  };

  return (
    <header className="app-header">
      <Link to="/" className="app-logo">予約カレンダー</Link>
      <nav className="app-nav">
        {user ? (
          <>
            <Link to="/menu" className="nav-link">メニュー</Link>
            <button className="nav-link nav-btn" onClick={handleLogout}>ログアウト</button>
          </>
        ) : (
          <Link to="/login" className="nav-link">ログイン</Link>
        )}
      </nav>
    </header>
  );
}

function AppContent() {
  return (
    <>
      <Header />
      <main className="app-main">
        <Suspense fallback={<div className="loading-screen">読み込み中...</div>}>
          <Routes>
            <Route path="/" element={<TopPage />} />
            <Route path="/login" element={<LoginPage />} />
            <Route path="/signup" element={<SignupPage />} />
            <Route path="/menu" element={<ProtectedRoute><MenuPage /></ProtectedRoute>} />
            <Route path="/calendar" element={<ProtectedRoute><CalendarPage /></ProtectedRoute>} />
            <Route path="/reserve/form" element={<ProtectedRoute><ReservationFormPage /></ProtectedRoute>} />
            <Route path="/reserve/confirm" element={<ProtectedRoute><ReserveConfirmPage /></ProtectedRoute>} />
            <Route path="/reservations" element={<ProtectedRoute><MyReservationsPage /></ProtectedRoute>} />
          </Routes>
        </Suspense>
      </main>
    </>
  );
}

export default function App() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const loginFn = useCallback((token: string, u: User) => {
    localStorage.setItem('token', token);
    setUser(u);
  }, []);

  const logoutFn = useCallback(() => {
    localStorage.removeItem('token');
    setUser(null);
  }, []);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      setLoading(false);
      return;
    }
    authService.getUser()
      .then(u => setUser(u))
      .catch(() => localStorage.removeItem('token'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    const handler = () => logoutFn();
    window.addEventListener('auth:logout', handler);
    return () => window.removeEventListener('auth:logout', handler);
  }, [logoutFn]);

  return (
    <AuthContext.Provider value={{ user, loading, login: loginFn, logout: logoutFn }}>
      <BrowserRouter>
        <AppContent />
      </BrowserRouter>
    </AuthContext.Provider>
  );
}
