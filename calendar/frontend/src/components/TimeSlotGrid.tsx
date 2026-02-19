import type { SlotItem } from '../types/slot';

interface TimeSlotGridProps {
  slots: SlotItem[];
  selectedTime: string | null;
  onSelect: (time: string) => void;
}

export default function TimeSlotGrid({ slots, selectedTime, onSelect }: TimeSlotGridProps) {
  const morningSlots = slots.filter(s => s.time < '12:00');
  const afternoonSlots = slots.filter(s => s.time >= '12:00');

  const renderSlots = (items: SlotItem[], label: string) => (
    <div className="timeslot-section">
      <h4 className="timeslot-section-title">{label}</h4>
      <div className="timeslot-grid">
        {items.map(slot => {
          const isSelected = slot.time === selectedTime;
          const classes = [
            'timeslot-cell',
            slot.reservable ? 'available' : 'unavailable',
            isSelected ? 'selected' : '',
          ].filter(Boolean).join(' ');

          return (
            <button
              key={slot.time}
              className={classes}
              disabled={!slot.reservable}
              onClick={() => onSelect(slot.time)}
            >
              <span className="timeslot-symbol">{slot.reservable ? '○' : '×'}</span>
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
