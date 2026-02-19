/**
 * 日本の祝日判定（フロント側）
 * バックエンド HolidayService.php と同一ロジック。
 * カレンダーの祝日バッジ表示用。
 */

const FIXED_HOLIDAYS: [number, number][] = [
  [1, 1],   // 元日
  [2, 11],  // 建国記念の日
  [2, 23],  // 天皇誕生日
  [4, 29],  // 昭和の日
  [5, 3],   // 憲法記念日
  [5, 4],   // みどりの日
  [5, 5],   // こどもの日
  [8, 11],  // 山の日
  [11, 3],  // 文化の日
  [11, 23], // 勤労感謝の日
];

function nthMonday(year: number, month: number, n: number): number {
  const first = new Date(year, month - 1, 1);
  const dayOfWeek = first.getDay(); // 0=Sun, 1=Mon, ...
  const mondayOffset = dayOfWeek <= 1 ? 1 - dayOfWeek : 8 - dayOfWeek;
  return mondayOffset + (n - 1) * 7;
}

function vernalEquinoxDay(year: number): number {
  return Math.floor(20.8431 + 0.242194 * (year - 1980) - Math.floor((year - 1980) / 4));
}

function autumnalEquinoxDay(year: number): number {
  return Math.floor(23.2488 + 0.242194 * (year - 1980) - Math.floor((year - 1980) / 4));
}

export function isJapaneseHoliday(date: Date): boolean {
  const y = date.getFullYear();
  const m = date.getMonth() + 1;
  const d = date.getDate();

  for (const [hm, hd] of FIXED_HOLIDAYS) {
    if (m === hm && d === hd) return true;
  }

  if (m === 1 && d === nthMonday(y, 1, 2)) return true;
  if (m === 3 && d === vernalEquinoxDay(y)) return true;
  if (m === 7 && d === nthMonday(y, 7, 3)) return true;
  if (m === 9 && d === nthMonday(y, 9, 3)) return true;
  if (m === 9 && d === autumnalEquinoxDay(y)) return true;
  if (m === 10 && d === nthMonday(y, 10, 2)) return true;

  return false;
}

export function formatDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

const WEEKDAY_LABELS = ['日', '月', '火', '水', '木', '金', '土'];
export function getWeekdayLabel(date: Date): string {
  return WEEKDAY_LABELS[date.getDay()];
}
