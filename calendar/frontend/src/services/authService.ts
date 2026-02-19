import { apiFetch } from './api';
import type { AuthResponse, LoginPayload, RegisterPayload, User } from '../types/auth';

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  return apiFetch<AuthResponse>('/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function login(payload: LoginPayload): Promise<AuthResponse> {
  return apiFetch<AuthResponse>('/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function logout(): Promise<void> {
  await apiFetch('/logout', { method: 'POST' });
}

export async function getUser(): Promise<User> {
  return apiFetch<User>('/user');
}
