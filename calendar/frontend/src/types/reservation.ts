export interface Reservation {
  id: number;
  department: string;
  doctor_id: string;
  doctor: string;
  date: string;
  time: string;
  purpose: string;
  created_at: string;
}

export interface CreateReservationPayload {
  department: string;
  date: string;
  time: string;
  purpose?: string;
}
