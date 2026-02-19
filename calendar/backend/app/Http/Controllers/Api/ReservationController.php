<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $reservationService,
    ) {}

    /**
     * GET /api/reservations
     * ログインユーザーの予約一覧を返す
     */
    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::where('user_id', $request->user()->id)
            ->with('doctor:id,name')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function (Reservation $r) {
                $dateStr = $r->date instanceof Carbon ? $r->date->format('Y-m-d') : (string) $r->date;
                return [
                    'id'         => $r->id,
                    'department' => $r->department,
                    'doctor_id'  => $r->doctor_id,
                    'doctor'     => $r->doctor?->name ?? '',
                    'date'       => $dateStr,
                    'time'       => $r->time,
                    'purpose'    => $r->purpose,
                    'created_at' => $r->created_at?->toIso8601String(),
                ];
            });

        return response()->json($reservations);
    }

    /**
     * POST /api/reservations
     * 予約を作成する
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $result = $this->reservationService->createReservation(
                department: $request->validated('department'),
                date: $request->validated('date'),
                time: $request->validated('time'),
                userId: $request->user()->id,
                purpose: $request->validated('purpose') ?? '',
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }

    /**
     * DELETE /api/reservations/{id}
     * 予約をキャンセルする
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->reservationService->cancelReservation(
                userId: $request->user()->id,
                reservationId: $id,
            );

            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
