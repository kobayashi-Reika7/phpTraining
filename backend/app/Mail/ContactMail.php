<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * お問い合わせメールの Mailable クラス
 *
 * Laravel の Mailable を使うと、メールのテンプレート化・テスト・ログ出力を統一的に扱える。
 * MAIL_MAILER=log に設定するだけで、実際に送信せずログファイルに出力される。
 *
 * Envelope = メールの封筒（差出人・件名）
 * Content  = メールの中身（テンプレートとデータ）
 */
class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array  $data      フォーム入力データ（name, email, comment, gender, kind, lang）
     * @param string $ip        送信者の IP アドレス
     * @param string $userAgent 送信者のブラウザ情報
     */
    public function __construct(
        private array $data,
        private string $ip,
        private string $userAgent,
    ) {}

    /**
     * メールの封筒情報（差出人と件名）
     *
     * from にはフォームに入力されたメールアドレスと名前をセット。
     * 件名は config/contact.php の mail_subject から取得。
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->data['email'], $this->data['name']),
            subject: config('contact.mail_subject'),
        );
    }

    /**
     * メール本文（Blade テンプレートとデータの紐付け）
     *
     * emails.contact は resources/views/emails/contact.blade.php を指す。
     * with でテンプレートに渡す変数を定義。
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'data'      => $this->data,
                'ip'        => $this->ip,
                'userAgent' => $this->userAgent,
            ],
        );
    }
}
