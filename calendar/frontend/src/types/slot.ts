export interface SlotItem {
  time: string;
  reservable: boolean;
}

export interface AvailabilityResponse {
  date: string;
  is_holiday: boolean;
  is_weekend?: boolean;
  reservable: boolean;
  reason: string | null;
  slots: SlotItem[];
}
