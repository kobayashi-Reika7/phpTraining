<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * GET /api/doctors
     * 全医師一覧を返す
     */
    public function index(): JsonResponse
    {
        $doctors = Doctor::orderBy('department')
            ->orderBy('name')
            ->get()
            ->map(fn (Doctor $d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'department' => $d->department,
                'schedules'  => $d->schedules,
            ]);

        return response()->json($doctors);
    }

    /**
     * GET /api/doctors?department=内科
     * 診療科別の医師一覧を返す
     */
    public function byDepartment(Request $request): JsonResponse
    {
        $department = $request->query('department', '');

        if (!$department) {
            return response()->json(['error' => '診療科を指定してください。'], 422);
        }

        $doctors = Doctor::where('department', $department)
            ->orderBy('name')
            ->get()
            ->map(fn (Doctor $d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'department' => $d->department,
                'schedules'  => $d->schedules,
            ]);

        return response()->json($doctors);
    }

    /**
     * GET /api/departments
     * 診療科の一覧（重複なし）を返す
     */
    public function departments(): JsonResponse
    {
        $departments = Doctor::select('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return response()->json($departments);
    }
}
