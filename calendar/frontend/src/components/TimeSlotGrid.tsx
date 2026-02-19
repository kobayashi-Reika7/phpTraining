import type { SlotItem } from '../types/slot';
import { formatDate } from '../utils/holiday';

interface TimeSlotGridProps {
  slots: SlotItem[];
  selectedTime: string | null;
  onSelect: (time: string) => void;
  date?: string;
}

export default function TimeSlotGrid({ slots, selectedTime, onSelect, date }: TimeSlotGridProps) {
  const now = new Date();
  const todayStr = formatDate(now);
  const isToday = date === todayStr;
  const currentHHMM = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

  const morningSlots = slots.filter(s => s.time < '12:00');
  const afternoonSlots = slots.filter(s => s.time >= '12:00');

  const renderSlots = (items: SlotItem[], label: string) => (
    <div className="timeslot-section">
      <h4 className="timeslot-section-title">{label}</h4>
      <div className="timeslot-grid">
        {items.map(slot => {
          const pastTime = isToday && slot.time <= currentHHMM;
          const canBook = slot.reservable && !pastTime;
          const isSelected = slot.time === selectedTime;
          const classes = [
            'timeslot-cell',
            canBook ? 'available' : 'unavailable',
            isSelected ? 'selected' : '',
          ].filter(Boolean).join(' ');

          return (
            <button
              key={slot.time}
              className={classes}
              disabled={!canBook}
              onClick={() => onSelect(slot.time)}
            >
              <span className="timeslot-symbol">{canBook ? '○' : '×'}</span>
              <span className="timeslot-time">{slot.time}</span>
            </button>
          );
        })}
      </div>
    </div>
  );

  return (
    <div className="timeslot-container">
      {renderSlots(morningSlots, '午前')}
      {renderSlots(afternoonSlots, '午後')}
    </div>
  );
}
