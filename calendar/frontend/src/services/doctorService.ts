import { apiFetch } from './api';

export async function getDepartments(): Promise<string[]> {
  return apiFetch<string[]>('/departments');
}
