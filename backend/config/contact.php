<?php

/**
 * コンタクトフォーム設定
 *
 * お問い合わせメールの送信先や件名を .env で管理する。
 * .env に値がなければデフォルト値が使われる。
 */
return [
    'admin_name'   => env('CONTACT_ADMIN_NAME', '管理者'),
    'admin_email'  => env('CONTACT_ADMIN_EMAIL', 'admin@example.jp'),
    'mail_subject' => env('CONTACT_MAIL_SUBJECT', 'コンタクトフォーム'),
];
