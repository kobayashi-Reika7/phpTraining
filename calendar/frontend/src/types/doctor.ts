export interface Doctor {
  id: string;
  name: string;
  department: string;
  schedules: Record<string, string[]>;
}
