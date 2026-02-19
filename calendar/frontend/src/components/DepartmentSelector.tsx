import { CATEGORIES, DEPARTMENTS_BY_CATEGORY } from '../constants/masterData';

interface DepartmentSelectorProps {
  selected: string;
  onSelect: (department: string) => void;
}

export default function DepartmentSelector({ selected, onSelect }: DepartmentSelectorProps) {
  return (
    <div className="department-selector">
      {CATEGORIES.map(cat => (
        <div key={cat.id} className="department-category">
          <h3 className="category-title">{cat.label}</h3>
          <div className="department-list">
            {DEPARTMENTS_BY_CATEGORY[cat.id]?.map(dept => (
              <button
                key={dept.id}
                className={`department-btn ${selected === dept.label ? 'active' : ''}`}
                onClick={() => onSelect(dept.label)}
              >
                {dept.label}
              </button>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}
