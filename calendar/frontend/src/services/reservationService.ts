import { apiFetch } from './api';
import type { CreateReservationPayload, Reservation } from '../types/reservation';

export async function getReservations(): Promise<Reservation[]> {
  return apiFetch<Reservation[]>('/reservations');
}

export async function createReservation(payload: CreateReservationPayload): Promise<Reservation> {
  return apiFetch<Reservation>('/reservations', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function cancelReservation(id: number): Promise<{ ok: boolean; id: number }> {
  return apiFetch(`/reservations/${id}`, { method: 'DELETE' });
}
