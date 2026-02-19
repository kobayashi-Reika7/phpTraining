<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    // 文字列 PK（auto-increment ではない）
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'department',
        'schedules',
    ];

    protected function casts(): array
    {
        return [
            'schedules' => 'array',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function bookedSlots(): HasMany
    {
        return $this->hasMany(BookedSlot::class);
    }

    /**
     * 指定曜日の勤務時間リストを返す
     * @param string $weekdayKey "mon","tue","wed","thu","fri","sat","sun"
     * @return string[] 例: ["09:00","09:15",...]
     */
    public function getScheduleForDay(string $weekdayKey): array
    {
        return $this->schedules[$weekdayKey] ?? [];
    }

    /**
     * 指定日時に勤務中か判定
     */
    public function isWorkingAt(string $date, string $time): bool
    {
        $weekdayKey = strtolower(date('D', strtotime($date)));
        // date('D') は "Mon","Tue",... → 小文字3文字に変換
        $dayMap = ['mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun'];
        $key = $dayMap[$weekdayKey] ?? '';

        return in_array($time, $this->getScheduleForDay($key), true);
    }
}
