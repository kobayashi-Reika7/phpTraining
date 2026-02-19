<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'department' => ['required', 'string', 'max:100'],
            'date'       => ['required', 'date_format:Y-m-d'],
            'time'       => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'purpose'    => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'department.required'  => '診療科を選択してください。',
            'date.required'        => '日付を選択してください。',
            'date.date_format'     => '日付の形式が不正です（YYYY-MM-DD）。',
            'time.required'        => '時間を選択してください。',
            'time.regex'           => '時間の形式が不正です（HH:mm）。',
            'purpose.max'          => '受診目的は100文字以内で入力してください。',
        ];
    }
}
