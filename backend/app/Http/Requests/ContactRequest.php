<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * コンタクトフォームのバリデーションリクエスト
 *
 * FormRequest を使うことで、バリデーションルールをコントローラから分離できる。
 * authorize() で認証チェック、rules() でルール定義、messages() で日本語エラーメッセージを返す。
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
     * バリデーションルール
     *
     * 既存 FuelPHP 版と同等のルールを Laravel 形式で定義。
     * - name: 必須、最大20文字、タブ・改行禁止（メールヘッダインジェクション対策）
     * - email: 必須、RFC 準拠のメール形式
     * - comment: 必須、最大400文字
     * - gender, kind: 許可値リスト（nullable なので未選択OK）
     * - lang: 配列で受け取り、各要素が許可値に含まれるかチェック
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'max:20', 'regex:/\A[^\r\n\t]*\z/u'],
            'email'   => ['required', 'email:rfc'],
            'comment' => ['required', 'max:400'],
            'gender'  => ['nullable', 'in:男性,女性'],
            'kind'    => ['nullable', 'in:,製品購入前のお問い合わせ,製品購入後のお問い合わせ,その他'],
            'lang'    => ['nullable', 'array'],
            'lang.*'  => ['in:PHP,Perl,Python'],
        ];
    }

    /**
     * 日本語エラーメッセージ
     *
     * Laravel デフォルトは英語なので、日本語ユーザー向けにカスタムメッセージを定義。
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
