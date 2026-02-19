<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService,
    ) {}

    /**
     * GET /api/slots?department=内科&date=2026-02-20
     * GET /api/slots?department=内科&dates[]=2026-02-20&dates[]=2026-02-21
     *
     * 1日分 or 複数日分の空き枠を返す
     */
    public function index(Request $request): JsonResponse
    {
        $department = $request->query('department', '');
        $userId = $request->user()?->id;

        // 複数日対応: dates[] が指定されていればそちらを使う
        $dates = $request->query('dates');
        if (is_array($dates) && count($dates) > 0) {
            $result = $this->availabilityService->getAvailabilityForDates($department, $dates, $userId);
            return response()->json($result);
        }

        // 1日分
        $date = $request->query('date', '');
        if (!$department || !$date) {
            return response()->json(['error' => '診療科と日付は必須です。'], 422);
        }

        $result = $this->availabilityService->getAvailabilityForDate($department, $date, $userId);
        return response()->json($result);
    }
}
