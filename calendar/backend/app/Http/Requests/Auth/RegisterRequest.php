<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => '名前を入力してください',
            'email.required'     => 'メールアドレスを入力してください',
            'email.email'        => '有効なメールアドレスを入力してください',
            'email.unique'       => 'このメールアドレスは既に登録されています',
            'password.required'  => 'パスワードを入力してください',
            'password.min'       => 'パスワードは6文字以上で入力してください',
            'password.confirmed' => 'パスワードが一致しません',
        ];
    }
}
