<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * 日本の祝日判定サービス
 *
 * 元プロジェクト reservation_service.py の _is_japanese_holiday() を完全移植。
 * フロント utils/holiday.ts と同一ロジックにすること。
 */
class HolidayService
{
    /** 固定祝日 (月, 日) */
    private const FIXED_HOLIDAYS = [
        [1, 1],   // 元日
        [2, 11],  // 建国記念の日
        [2, 23],  // 天皇誕生日（令和）
        [4, 29],  // 昭和の日
        [5, 3],   // 憲法記念日
        [5, 4],   // みどりの日
        [5, 5],   // こどもの日
        [8, 11],  // 山の日
        [11, 3],  // 文化の日
        [11, 23], // 勤労感謝の日
    ];

    public function isJapaneseHoliday(string $dateStr): bool
    {
        try {
            $dt = Carbon::createFromFormat('Y-m-d', $dateStr);
        } catch (\Exception) {
            return false;
        }

        if (!$dt) {
            return false;
        }

        $y = $dt->year;
        $m = $dt->month;
        $d = $dt->day;

        // 固定祝日
        foreach (self::FIXED_HOLIDAYS as [$hm, $hd]) {
            if ($m === $hm && $d === $hd) {
                return true;
            }
        }

        // 成人の日（1月第2月曜日）
        if ($m === 1 && $d === $this->nthMonday($y, 1, 2)) {
            return true;
        }

        // 春分の日
        if ($m === 3 && $d === $this->vernalEquinoxDay($y)) {
            return true;
        }

        // 海の日（7月第3月曜日）
        if ($m === 7 && $d === $this->nthMonday($y, 7, 3)) {
            return true;
        }

        // 敬老の日（9月第3月曜日）
        if ($m === 9 && $d === $this->nthMonday($y, 9, 3)) {
            return true;
        }

        // 秋分の日
        if ($m === 9 && $d === $this->autumnalEquinoxDay($y)) {
            return true;
        }

        // スポーツの日（10月第2月曜日）
        if ($m === 10 && $d === $this->nthMonday($y, 10, 2)) {
            return true;
        }

        return false;
    }

    /**
     * year/month の第n月曜日の日を返す
     * Carbon の dayOfWeekIso: 1=Monday
     */
    private function nthMonday(int $year, int $month, int $n): int
    {
        $first = Carbon::create($year, $month, 1);
        // weekday() で 0=Monday (Python 互換の計算)
        $dayOfWeek = $first->dayOfWeekIso - 1; // 0=Mon, 1=Tue, ...
        return ($n - 1) * 7 + 1 + (7 - $dayOfWeek) % 7;
    }

    /** 春分の日（簡易天文計算: 2000〜2099年用） */
    private function vernalEquinoxDay(int $year): int
    {
        return (int) floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }

    /** 秋分の日（簡易天文計算: 2000〜2099年用） */
    private function autumnalEquinoxDay(int $year): int
    {
        return (int) floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
}
