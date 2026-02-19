<?php

namespace App\Services;

use App\Models\BookedSlot;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 予約作成・キャンセル サービス
 *
 * 元プロジェクト reservation_service.py の create_reservation / cancel_reservation を移植。
 * ダブルブッキング防止: RDB トランザクション + booked_slots UNIQUE 制約 の2段構え。
 */
class ReservationService
{
    public function __construct(
        private HolidayService $holidayService,
        private AvailabilityService $availabilityService,
    ) {}

    /**
     * 予約を確定する。担当医は自動割当。
     *
     * @throws \RuntimeException バリデーション / ダブルブッキング時
     */
    public function createReservation(
        string $department,
        string $date,
        string $time,
        int $userId,
        string $purpose = '',
    ): array {
        $department = trim($department);
        $date = trim($date);
        $time = trim($time);

        // 過去日チェック
        $dt = Carbon::createFromFormat('Y-m-d', $date);
        $today = Carbon::today();
        if ($dt->lt($today)) {
            throw new \RuntimeException('過去の日付は予約できません。別の日をお選びください。');
        }

        // 土日チェック
        if ($dt->isWeekend()) {
            throw new \RuntimeException('土日は予約できません。平日をお選びください。');
        }

        // 祝日チェック
        if ($this->holidayService->isJapaneseHoliday($date)) {
            throw new \RuntimeException('祝日のため予約できません。別の日をお選びください。');
        }

        // 今日の過去時刻チェック
        if ($dt->isToday() && $time <= Carbon::now()->format('H:i')) {
            throw new \RuntimeException('この時刻はすでに過ぎています。別の時間をお選びください。');
        }

        // 同一ユーザー同診療科+日+時間の二重予約チェック
        $exists = Reservation::where('user_id', $userId)
            ->where('department', $department)
            ->where('date', $date)
            ->where('time', $time)
            ->exists();
        if ($exists) {
            throw new \RuntimeException('この診療科・日時はすでに予約済みです。予約一覧からご確認ください。');
        }

        // 空いている医師を取得
        $availableDoctors = $this->availabilityService->getAvailableDoctors($department, $date, $time);

        return DB::transaction(function () use ($department, $date, $time, $userId, $purpose, $availableDoctors) {
            // 各候補医師について booked_slots の UNIQUE 制約で原子的確保を試みる
            $assignedDoctor = null;

            foreach ($availableDoctors as $doctor) {
                try {
                    BookedSlot::create([
                        'doctor_id'  => $doctor->id,
                        'date'       => $date,
                        'time'       => $time,
                        'department' => $department,
                        'user_id'    => $userId,
                    ]);
                    $assignedDoctor = $doctor;
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    // UNIQUE 制約違反 = 他のリクエストが先に確保 → 次の医師を試す
                    if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')) {
                        Log::info("Slot already taken for doctor {$doctor->id}, trying next");
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$assignedDoctor) {
                throw new \RuntimeException('この時間は現在予約できません。別の時間をお選びください。');
            }

            // 予約レコードを作成
            $reservation = Reservation::create([
                'user_id'    => $userId,
                'doctor_id'  => $assignedDoctor->id,
                'department' => $department,
                'date'       => $date,
                'time'       => $time,
                'purpose'    => $purpose,
            ]);

            // booked_slots に reservation_id を記録（キャンセル時の参照用）
            BookedSlot::where('doctor_id', $assignedDoctor->id)
                ->where('date', $date)
                ->where('time', $time)
                ->update(['reservation_id' => $reservation->id]);

            Log::info("Reservation created: id={$reservation->id} doctor={$assignedDoctor->id}");

            return [
                'id'         => $reservation->id,
                'department' => $department,
                'doctor_id'  => $assignedDoctor->id,
                'doctor'     => $assignedDoctor->name,
                'date'       => $date,
                'time'       => $time,
                'purpose'    => $purpose,
                'created_at' => $reservation->created_at->toIso8601String(),
            ];
        });
    }

    /**
     * 既存予約を変更する（同一トランザクションで旧スロット解放→新スロット確保→レコード更新）
     *
     * @throws \RuntimeException バリデーション / ダブルブッキング時
     */
    public function updateReservation(
        int $reservationId,
        string $department,
        string $date,
        string $time,
        int $userId,
        string $purpose = '',
    ): array {
        $department = trim($department);
        $date = trim($date);
        $time = trim($time);

        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $userId)
            ->first();

        if (!$reservation) {
            throw new \RuntimeException('指定された予約が見つかりません。');
        }

        $oldDateStr = $reservation->date instanceof Carbon
            ? $reservation->date->format('Y-m-d')
            : (string) $reservation->date;

        // 変更なしの場合は早期リターン
        if ($reservation->department === $department
            && $oldDateStr === $date
            && $reservation->time === $time
            && $reservation->purpose === $purpose) {
            return [
                'id'         => $reservation->id,
                'department' => $reservation->department,
                'doctor_id'  => $reservation->doctor_id,
                'doctor'     => $reservation->doctor?->name ?? '',
                'date'       => $oldDateStr,
                'time'       => $reservation->time,
                'purpose'    => $reservation->purpose,
                'created_at' => $reservation->created_at->toIso8601String(),
            ];
        }

        // 新しい日時のバリデーション
        $dt = Carbon::createFromFormat('Y-m-d', $date);
        $today = Carbon::today();
        if ($dt->lt($today)) {
            throw new \RuntimeException('過去の日付は予約できません。別の日をお選びください。');
        }
        if ($dt->isWeekend()) {
            throw new \RuntimeException('土日は予約できません。平日をお選びください。');
        }
        if ($this->holidayService->isJapaneseHoliday($date)) {
            throw new \RuntimeException('祝日のため予約できません。別の日をお選びください。');
        }
        if ($dt->isToday() && $time <= Carbon::now()->format('H:i')) {
            throw new \RuntimeException('この時刻はすでに過ぎています。別の時間をお選びください。');
        }

        // 同一ユーザー同診療科+日+時間の重複チェック（自分自身は除外）
        $duplicate = Reservation::where('user_id', $userId)
            ->where('department', $department)
            ->where('date', $date)
            ->where('time', $time)
            ->where('id', '!=', $reservationId)
            ->exists();
        if ($duplicate) {
            throw new \RuntimeException('この診療科・日時はすでに予約済みです。');
        }

        // 日時 or 診療科が変わった場合のみスロット再確保が必要
        $slotChanged = $reservation->department !== $department
            || $oldDateStr !== $date
            || $reservation->time !== $time;

        $availableDoctors = $slotChanged
            ? $this->availabilityService->getAvailableDoctors($department, $date, $time)
            : collect();

        return DB::transaction(function () use (
            $reservation, $department, $date, $time, $userId, $purpose,
            $slotChanged, $availableDoctors, $oldDateStr,
        ) {
            $assignedDoctorId = $reservation->doctor_id;
            $assignedDoctorName = $reservation->doctor?->name ?? '';

            if ($slotChanged) {
                // 旧スロットを解放
                BookedSlot::where('doctor_id', $reservation->doctor_id)
                    ->where('date', $oldDateStr)
                    ->where('time', $reservation->time)
                    ->delete();

                // 新スロットを確保
                $assignedDoctor = null;
                foreach ($availableDoctors as $doctor) {
                    try {
                        BookedSlot::create([
                            'doctor_id'  => $doctor->id,
                            'date'       => $date,
                            'time'       => $time,
                            'department' => $department,
                            'user_id'    => $userId,
                        ]);
                        $assignedDoctor = $doctor;
                        break;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')) {
                            continue;
                        }
                        throw $e;
                    }
                }

                if (!$assignedDoctor) {
                    throw new \RuntimeException('変更先の時間は現在予約できません。別の時間をお選びください。');
                }

                $assignedDoctorId = $assignedDoctor->id;
                $assignedDoctorName = $assignedDoctor->name;
            }

            // 予約レコードを更新
            $reservation->update([
                'doctor_id'  => $assignedDoctorId,
                'department' => $department,
                'date'       => $date,
                'time'       => $time,
                'purpose'    => $purpose,
            ]);

            // booked_slots に reservation_id を記録
            if ($slotChanged) {
                BookedSlot::where('doctor_id', $assignedDoctorId)
                    ->where('date', $date)
                    ->where('time', $time)
                    ->update(['reservation_id' => $reservation->id]);
            }

            Log::info("Reservation updated: id={$reservation->id} doctor={$assignedDoctorId}");

            return [
                'id'         => $reservation->id,
                'department' => $department,
                'doctor_id'  => $assignedDoctorId,
                'doctor'     => $assignedDoctorName,
                'date'       => $date,
                'time'       => $time,
                'purpose'    => $purpose,
                'created_at' => $reservation->created_at->toIso8601String(),
            ];
        });
    }

    /**
     * 予約をキャンセルする
     *
     * 1. reservations レコードを読み取り doctor_id/date/time を取得
     * 2. booked_slots から該当スロットを削除（解放）
     * 3. reservations レコードを削除
     */
    public function cancelReservation(int $userId, int $reservationId): array
    {
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $userId)
            ->first();

        if (!$reservation) {
            throw new \RuntimeException('指定された予約が見つかりません。');
        }

        return DB::transaction(function () use ($reservation) {
            $dateStr = $reservation->date instanceof Carbon
                ? $reservation->date->format('Y-m-d')
                : (string) $reservation->date;

            // booked_slots のスロットを解放
            BookedSlot::where('doctor_id', $reservation->doctor_id)
                ->where('date', $dateStr)
                ->where('time', $reservation->time)
                ->delete();

            $reservationId = $reservation->id;
            $reservation->delete();

            Log::info("Reservation cancelled: id={$reservationId}");

            return ['ok' => true, 'id' => $reservationId];
        });
    }
}
