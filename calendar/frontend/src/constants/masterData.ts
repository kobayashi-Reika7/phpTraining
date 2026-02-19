export interface Category {
  id: string;
  label: string;
}

export interface Department {
  id: string;
  label: string;
}

export const CATEGORIES: Category[] = [
  { id: 'internal', label: '内科系' },
  { id: 'surgical', label: '外科系' },
  { id: 'pediatric_women', label: '小児・女性' },
  { id: 'examination', label: '検査' },
  { id: 'rehabilitation', label: 'リハビリ' },
];

export const DEPARTMENTS_BY_CATEGORY: Record<string, Department[]> = {
  internal: [
    { id: 'cardiology', label: '循環器内科' },
    { id: 'gastroenterology', label: '消化器内科' },
    { id: 'respiratory', label: '呼吸器内科' },
    { id: 'nephrology', label: '腎臓内科' },
    { id: 'neurology', label: '神経内科' },
  ],
  surgical: [
    { id: 'orthopedics', label: '整形外科' },
    { id: 'ophthalmology', label: '眼科' },
    { id: 'otolaryngology', label: '耳鼻咽喉科' },
    { id: 'dermatology', label: '皮膚科' },
    { id: 'urology', label: '泌尿器科' },
  ],
  pediatric_women: [
    { id: 'pediatrics', label: '小児科' },
    { id: 'obstetrics', label: '産婦人科' },
  ],
  examination: [
    { id: 'radiology', label: '画像診断・検査' },
    { id: 'lab', label: '臨床検査' },
  ],
  rehabilitation: [
    { id: 'rehab', label: 'リハビリテーション科' },
  ],
};

export const ALL_DEPARTMENTS: Department[] = Object.values(DEPARTMENTS_BY_CATEGORY).flat();

export const PURPOSES = [
  { id: 'first', label: '初診' },
  { id: 'follow', label: '再診' },
] as const;

export function getTimeSlots(): string[] {
  const slots: string[] = [];
  for (let h = 9; h < 17; h++) {
    for (const m of [0, 15, 30, 45]) {
      slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
    }
  }
  return slots;
}
