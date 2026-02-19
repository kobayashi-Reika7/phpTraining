<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

/**
 * 17名の担当医データを投入するシーダー
 *
 * 元プロジェクト backend/scripts/seed_doctors_data.py から移植。
 * 診療科名は frontend/src/constants/masterData.ts の label と完全一致させること。
 * 15分刻み 09:00〜16:45（フロントの getTimeSlots() と整合）。
 */
class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $weekdayMorning   = self::slots(9, 0, 12, 0);
        $weekdayAfternoon = self::slots(13, 0, 17, 0);
        $weekdayFull      = array_merge($weekdayMorning, $weekdayAfternoon);
        $wedAm            = self::slots(9, 0, 12, 0);
        $friAm            = self::slots(9, 0, 12, 0);
        $empty            = [];

        $doctors = [
            // ── 循環器内科 ──
            [
                'id' => 'doc_cardiology_01',
                'name' => '山田 太郎',
                'department' => '循環器内科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $wedAm,
                    'thu' => $weekdayFull, 'fri' => $friAm, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            [
                'id' => 'doc_cardiology_02',
                'name' => '佐藤 花子',
                'department' => '循環器内科',
                'schedules' => [
                    'mon' => $weekdayAfternoon, 'tue' => $weekdayMorning, 'wed' => $weekdayFull,
                    'thu' => $empty, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 消化器内科 ──
            [
                'id' => 'doc_gastro_01',
                'name' => '鈴木 一郎',
                'department' => '消化器内科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $empty,
                    'thu' => $weekdayFull, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 呼吸器内科 ──
            [
                'id' => 'doc_respiratory_01',
                'name' => '高橋 美咲',
                'department' => '呼吸器内科',
                'schedules' => [
                    'mon' => $weekdayAfternoon, 'tue' => $weekdayFull, 'wed' => $weekdayMorning,
                    'thu' => $weekdayAfternoon, 'fri' => $weekdayMorning, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 腎臓内科 ──
            [
                'id' => 'doc_nephrology_01',
                'name' => '伊藤 健',
                'department' => '腎臓内科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $empty, 'wed' => $weekdayFull,
                    'thu' => $weekdayFull, 'fri' => $weekdayMorning, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 神経内科 ──
            [
                'id' => 'doc_neurology_01',
                'name' => '渡辺 直子',
                'department' => '神経内科',
                'schedules' => [
                    'mon' => $weekdayMorning, 'tue' => $weekdayFull, 'wed' => $weekdayAfternoon,
                    'thu' => $weekdayFull, 'fri' => $weekdayAfternoon, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 整形外科 ──
            [
                'id' => 'doc_ortho_01',
                'name' => '中村 大輔',
                'department' => '整形外科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayMorning, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            [
                'id' => 'doc_ortho_02',
                'name' => '小林 恵子',
                'department' => '整形外科',
                'schedules' => [
                    'mon' => $empty, 'tue' => $weekdayAfternoon, 'wed' => $weekdayMorning,
                    'thu' => $weekdayFull, 'fri' => $weekdayAfternoon, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 眼科 ──
            [
                'id' => 'doc_ophthalmology_01',
                'name' => '加藤 翔太',
                'department' => '眼科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayMorning, 'wed' => $weekdayFull,
                    'thu' => $weekdayAfternoon, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 耳鼻咽喉科 ──
            [
                'id' => 'doc_oto_01',
                'name' => '吉田 優',
                'department' => '耳鼻咽喉科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayMorning,
                    'thu' => $weekdayFull, 'fri' => $weekdayAfternoon, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 皮膚科 ──
            [
                'id' => 'doc_dermatology_01',
                'name' => '松本 彩',
                'department' => '皮膚科',
                'schedules' => [
                    'mon' => $weekdayAfternoon, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayMorning, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 泌尿器科 ──
            [
                'id' => 'doc_urology_01',
                'name' => '井上 誠',
                'department' => '泌尿器科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayMorning, 'wed' => $weekdayAfternoon,
                    'thu' => $weekdayFull, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 小児科 ──
            [
                'id' => 'doc_pediatrics_01',
                'name' => '木村 由美',
                'department' => '小児科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayMorning, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            [
                'id' => 'doc_pediatrics_02',
                'name' => '林 拓也',
                'department' => '小児科',
                'schedules' => [
                    'mon' => $empty, 'tue' => $weekdayAfternoon, 'wed' => $weekdayMorning,
                    'thu' => $weekdayFull, 'fri' => $weekdayAfternoon, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 産婦人科 ──
            [
                'id' => 'doc_obstetrics_01',
                'name' => '斎藤 香織',
                'department' => '産婦人科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayMorning, 'wed' => $weekdayFull,
                    'thu' => $weekdayAfternoon, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 画像診断・検査 ──
            [
                'id' => 'doc_radiology_01',
                'name' => '山口 聡',
                'department' => '画像診断・検査',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayFull, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── 臨床検査 ──
            [
                'id' => 'doc_lab_01',
                'name' => '松田 裕子',
                'department' => '臨床検査',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayFull, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
            // ── リハビリテーション科 ──
            [
                'id' => 'doc_rehab_01',
                'name' => '石川 浩二',
                'department' => 'リハビリテーション科',
                'schedules' => [
                    'mon' => $weekdayFull, 'tue' => $weekdayFull, 'wed' => $weekdayFull,
                    'thu' => $weekdayFull, 'fri' => $weekdayFull, 'sat' => $empty, 'sun' => $empty,
                ],
            ],
        ];

        foreach ($doctors as $data) {
            Doctor::updateOrCreate(['id' => $data['id']], $data);
        }
    }

    /**
     * 15分刻みの時間リストを生成（start 含む〜end 未満）
     * @return string[]
     */
    private static function slots(int $startH, int $startM, int $endH, int $endM): array
    {
        $out = [];
        $h = $startH;
        $m = $startM;
        while ($h < $endH || ($h === $endH && $m < $endM)) {
            $out[] = sprintf('%02d:%02d', $h, $m);
            $m += 15;
            if ($m >= 60) {
                $h++;
                $m = 0;
            }
        }
        return $out;
    }
}
