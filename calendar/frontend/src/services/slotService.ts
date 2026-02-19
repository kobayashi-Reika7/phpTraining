import { apiFetch } from './api';
import type { AvailabilityResponse } from '../types/slot';

export async function getSlots(department: string, date: string): Promise<AvailabilityResponse> {
  const params = new URLSearchParams({ department, date });
  return apiFetch<AvailabilityResponse>(`/slots?${params}`);
}

export async function getSlotsBulk(department: string, dates: string[]): Promise<AvailabilityResponse[]> {
  const params = new URLSearchParams({ department });
  dates.forEach(d => params.append('dates[]', d));
  return apiFetch<AvailabilityResponse[]>(`/slots?${params}`);
}
