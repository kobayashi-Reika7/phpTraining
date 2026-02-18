<?php

namespace App\Services;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * コンタクトフォームのビジネスロジック
 *
 * コントローラはリクエスト/レスポンスの橋渡しに徹し、
 * メールの組み立て・送信といったビジネスロジックは Service 層に分離する。
 */
class ContactService
{
    /**
     * お問い合わせメールを管理者に送信する
     *
     * @param array  $data      バリデーション済みのフォームデータ
     * @param string $ip        送信者の IP アドレス（ログ記録用）
     * @param string $userAgent 送信者のブラウザ情報（ログ記録用）
     */
    public function send(array $data, string $ip, string $userAgent = ''): void
    {
        $adminEmail = config('contact.admin_email');

        Mail::to($adminEmail)
            ->send(new ContactMail($data, $ip, $userAgent));

        // 送信成功をログに記録（監査・トラブルシューティング用）
        Log::info('Contact form submitted', [
            'from'  => $data['email'],
            'to'    => $adminEmail,
            'ip'    => $ip,
        ]);
    }
}
