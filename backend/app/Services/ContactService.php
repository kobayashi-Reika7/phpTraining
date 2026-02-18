<?php

namespace App\Services;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

/**
 * コンタクトフォームのビジネスロジック
 *
 * コントローラはリクエスト/レスポンスの橋渡しに徹し、
 * メールの組み立て・送信といったビジネスロジックは Service 層に分離する。
 * こうすることでテストやメンテナンスが容易になる。
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
    public function send(array $data, string $ip, ?string $userAgent): void
    {
        // config/contact.php の admin_email 宛にメールを送信
        // MAIL_MAILER=log の場合、実際にはメール送信せずログに出力される
        Mail::to(config('contact.admin_email'))
            ->send(new ContactMail($data, $ip, $userAgent ?? ''));
    }
}
