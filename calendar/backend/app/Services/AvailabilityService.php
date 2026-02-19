<?php

namespace App\Services;

use App\Models\BookedSlot;
use App\Models\Doctor;
use App\Models\Reservation;
use Carbon\Carbon;

/**
 * 空き枠算出サービス
 *
 * 元プロジェクト reservation_service.py の get_availability_for_dates() を移植。
 * 医師取得1回 + 予約取得1回 = DB 2クエリで全日分を計算（N+1 回避）。
 */
class AvailabilityService
{
    /** 15分刻み 09:00〜16:45（フロント getTimeSlots() と一致） */
    public const TIME_SLOTS = [
        '09:00', '09:15', '09:30', '09:45',
        '10:00', '10:15', '10:30', '10:45',
        '11:00', '11:15', '11:30', '11:45',
        '12:00', '12:15', '12:30', '12:45',
        '13:00', '13:15', '13:30', '13:45',
        '14:00', '14:15', '14:30', '14:45',
        '15:00', '15:15', '15:30', '15:45',
        '16:00', '16:15', '16:30', '16:45',
    ];

    private const WEEKDAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public function __construct(
        private HolidayService $holidayService,
    ) {}

    /**
     * 複数日分の空き状況を一括で返す（高速版）
     *
     * @param string   $department 診療科名
     * @param string[] $dates      日付配列 "YYYY-MM-DD"
     * @param int|null $userId     ログインユーザーID（自分の予約済みを×にする）
     * @return array<int, array{date: string, is_holiday: bool, reservable: bool, reason: ?string, slots: array}>
     */
    public function getAvailabilityForDates(string $department, array $dates, ?int $userId = null): array
    {
        $today = Carbon::today();
        $allFalse = array_map(fn (string $t) => ['time' => $t, 'reservable' => false], self::TIME_SLOTS);

        $results = [];
        $datesToCompute = [];

        // 過去日・祝日は即決定
        foreach ($dates as $date) {
            try {
                $dt = Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Exception) {
                $results[$date] = ['date' => $date, 'is_holiday' => false, 'reservable' => false, 'reason' => 'closed', 'slots' => $allFalse];
                continue;
            }

            if ($dt->lt($today)) {
                $results[$date] = ['date' => $date, 'is_holiday' => false, 'reservable' => false, 'reason' => 'past', 'slots' => $allFalse];
                continue;
            }

            if ($dt->isWeekend()) {
                $results[$date] = ['date' => $date, 'is_holiday' => false, 'is_weekend' => true, 'reservable' => false, 'reason' => 'weekend', 'slots' => $allFalse];
                continue;
            }

            if ($this->holidayService->isJapaneseHoliday($date)) {
                $results[$date] = ['date' => $date, 'is_holiday' => true, 'is_weekend' => false, 'reservable' => false, 'reason' => 'holiday', 'slots' => $allFalse];
                continue;
            }

            $datesToCompute[] = $date;
        }

        if (empty($datesToCompute)) {
            return array_map(fn (string $d) => $results[$d], $dates);
        }

        // 医師取得（1回のみ）
        $doctors = Doctor::where('department', $department)->get();

        if ($doctors->isEmpty()) {
            foreach ($datesToCompute as $date) {
                $results[$date] = ['date' => $date, 'is_holiday' => false, 'reservable' => false, 'reason' => null, 'slots' => $allFalse];
            }
            return array_map(fn (string $d) => $results[$d], $dates);
        }

        $doctorIds = $doctors->pluck('id')->all();

        // 予約済みスロットを一括取得（1回のみ）: (doctor_id, date, time) のセット
        $reserved = $this->getReservedSlotsBulk($doctorIds, $datesToCompute);

        // ユーザーの既存予約を取得（同一診療科+日+時間の二重予約防止）
        $userBooked = [];
        if ($userId) {
            $userBooked = $this->getUserBookedSlots($userId, $department, $datesToCompute);
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $todayStr = $now->format('Y-m-d');

        // 各日・各時間の空き判定
        foreach ($datesToCompute as $date) {
            $isToday = ($date === $todayStr);
            $slotList = [];
            foreach (self::TIME_SLOTS as $time) {
                // 当日の過去時刻は予約不可
                if ($isToday && $time <= $currentTime) {
                    $slotList[] = ['time' => $time, 'reservable' => false];
                    continue;
                }

                // このユーザーが既に同じ診療科+日+時間で予約済みなら×
                if (isset($userBooked["{$date}_{$time}"])) {
                    $slotList[] = ['time' => $time, 'reservable' => false];
                    continue;
                }

                $available = false;
                foreach ($doctors as $doctor) {
                    if ($doctor->isWorkingAt($date, $time) && !isset($reserved["{$doctor->id}_{$date}_{$time}"])) {
                        $available = true;
                        break;
                    }
                }
                $slotList[] = ['time' => $time, 'reservable' => $available];
            }

            $anyOk = collect($slotList)->contains('reservable', true);
            $results[$date] = ['date' => $date, 'is_holiday' => false, 'reservable' => $anyOk, 'reason' => null, 'slots' => $slotList];
        }

        return array_map(fn (string $d) => $results[$d], $dates);
    }

    /**
     * 1日分の空き状況を返す
     */
    public function getAvailabilityForDate(string $department, string $date, ?int $userId = null): array
    {
        $results = $this->getAvailabilityForDates($department, [$date], $userId);
        return $results[0] ?? ['date' => $date, 'is_holiday' => false, 'reservable' => false, 'reason' => null, 'slots' => []];
    }

    /**
     * 指定診療科・日時で空いている医師を返す
     *
     * @return \Illuminate\Support\Collection<int, Doctor>
     */
    public function getAvailableDoctors(string $department, string $date, string $time): \Illuminate\Support\Collection
    {
        $doctors = Doctor::where('department', $department)->get();
        $reserved = $this->getReservedSlotsBulk($doctors->pluck('id')->all(), [$date]);

        return $doctors->filter(function (Doctor $doc) use ($date, $time, $reserved) {
            return $doc->isWorkingAt($date, $time) && !isset($reserved["{$doc->id}_{$date}_{$time}"]);
        })->values();
    }

    /**
     * 複数医師・複数日の予約済みスロットを一括取得
     * @return array<string, true> キー: "{doctorId}_{date}_{time}"
     */
    private function getReservedSlotsBulk(array $doctorIds, array $dates): array
    {
        if (empty($doctorIds) || empty($dates)) {
            return [];
        }

        $reserved = [];

        // booked_slots テーブルから取得
        BookedSlot::whereIn('doctor_id', $doctorIds)
            ->whereIn('date', $dates)
            ->select('doctor_id', 'date', 'time')
            ->each(function ($slot) use (&$reserved) {
                $dateStr = $slot->date instanceof Carbon ? $slot->date->format('Y-m-d') : (string) $slot->date;
                $reserved["{$slot->doctor_id}_{$dateStr}_{$slot->time}"] = true;
            });

        return $reserved;
    }

    /**
     * 指定ユーザーの予約済み (date, time) を取得
     * @return array<string, true> キー: "{date}_{time}"
     */
    private function getUserBookedSlots(int $userId, string $department, array $dates): array
    {
        $booked = [];

        Reservation::where('user_id', $userId)
            ->where('department', $department)
            ->whereIn('date', $dates)
            ->select('date', 'time')
            ->each(function ($res) use (&$booked) {
                $dateStr = $res->date instanceof Carbon ? $res->date->format('Y-m-d') : (string) $res->date;
                $booked["{$dateStr}_{$res->time}"] = true;
            });

        return $booked;
    }

    /**
     * 曜日キーを取得 (mon, tue, ...)
     */
    public static function weekdayKey(string $dateStr): string
    {
        try {
            $dt = Carbon::createFromFormat('Y-m-d', $dateStr);
            return self::WEEKDAY_KEYS[$dt->dayOfWeekIso - 1]; // 1=Mon → index 0
        } catch (\Exception) {
            return 'sun';
        }
    }
}
