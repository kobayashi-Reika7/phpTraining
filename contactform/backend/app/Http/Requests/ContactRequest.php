<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * コンタクトフォームのバリデーションリクエスト
 *
 * FormRequest を使うことで、バリデーションルールをコントローラから分離できる。
 * authorize() で認証チェック、rules() でルール定義、messages() で日本語エラーメッセージを返す。
 *
 * prepareForValidation(): バリデーション前に入力値をサニタイズ（トリム等）
 * rules(): config/contact.php の選択肢を参照してハードコードを排除
 */
class ContactRequest extends FormRequest
{
    /**
     * このリクエストを許可するか（認証不要なので常に true）
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーション前の入力値サニタイズ
     *
     * ユーザー入力の前後の空白を除去する。
     * これにより「 田中太郎 」→「田中太郎」のように正規化される。
     * FuelPHP 版の add_rule('trim') に相当。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'    => trim($this->input('name', '')),
            'email'   => trim($this->input('email', '')),
            'comment' => trim($this->input('comment', '')),
        ]);
    }

    /**
     * バリデーションルール
     *
     * 選択肢の許可値は config/contact.php から取得する。
     * ハードコードを避けることで、選択肢の追加・変更が設定ファイルだけで完結する。
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'max:20', 'regex:/\A[^\r\n\t]*\z/u'],
            'email'   => ['required', 'email:rfc'],
            'comment' => ['required', 'max:400'],
            'gender'  => ['nullable', Rule::in(config('contact.gender_options'))],
            'kind'    => ['nullable', Rule::in(config('contact.kind_options'))],
            'lang'    => ['nullable', 'array'],
            'lang.*'  => [Rule::in(config('contact.lang_options'))],
        ];
    }

    /**
     * 日本語エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required'    => '名前は必須です',
            'name.max'         => '名前は20文字以内で入力してください',
            'name.regex'       => '名前にタブや改行を含めることはできません',
            'email.required'   => 'メールアドレスは必須です',
            'email.email'      => '有効なメールアドレスを入力してください',
            'comment.required' => 'コメントは必須です',
            'comment.max'      => 'コメントは400文字以内で入力してください',
            'gender.in'        => '無効な選択肢です',
            'kind.in'          => '無効な選択肢です',
            'lang.array'       => '無効な形式です',
            'lang.*.in'        => '無効な選択肢です',
        ];
    }

    /**
     * バリデーション失敗時に JSON レスポンスを返す
     *
     * デフォルトでは Web 用のリダイレクトが発生するが、
     * API なので JSON で 422 レスポンスを返すようオーバーライドする。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'バリデーションエラーがあります',
                'errors'  => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
