import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import * as authService from '../services/authService';
import { ApiError } from '../services/api';

export default function SignupPage() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password !== passwordConfirm) {
      setError('パスワードが一致しません。');
      return;
    }

    setLoading(true);
    try {
      const res = await authService.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirm,
      });
      login(res.token, res.user);
      navigate('/menu');
    } catch (err) {
      if (err instanceof ApiError) {
        const errors = err.data?.errors as Record<string, string[]> | undefined;
        if (errors) {
          setError(Object.values(errors).flat().join('\n'));
        } else {
          setError(err.message || '登録に失敗しました。');
        }
      } else {
        setError('通信エラーが発生しました。');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="page auth-page">
      <div className="auth-card">
        <h1 className="auth-title">新規登録</h1>

        {error && <div className="error-message">{error}</div>}

        <form onSubmit={handleSubmit} className="auth-form">
          <label className="form-label">
            お名前
            <input
              type="text"
              className="form-input"
              value={name}
              onChange={e => setName(e.target.value)}
              required
              autoComplete="name"
            />
          </label>

          <label className="form-label">
            メールアドレス
            <input
              type="email"
              className="form-input"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              autoComplete="email"
            />
          </label>

          <label className="form-label">
            パスワード（6 文字以上）
            <input
              type="password"
              className="form-input"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              minLength={6}
              autoComplete="new-password"
            />
          </label>

          <label className="form-label">
            パスワード（確認）
            <input
              type="password"
              className="form-input"
              value={passwordConfirm}
              onChange={e => setPasswordConfirm(e.target.value)}
              required
              minLength={6}
              autoComplete="new-password"
            />
          </label>

          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? '登録中...' : '新規登録'}
          </button>
        </form>

        <p className="auth-link">
          アカウントをお持ちの方は <Link to="/login">ログイン</Link>
        </p>
      </div>
    </div>
  );
}
