<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\mail\CustomUserMail;
use local_subscriptions\mail\PasswordResetMail;
use local_subscriptions\admin\AdminLog;

final class UserEmailService {

    public static function send_custom_email(
        int $userid,
        string $subject,
        string $messagehtml,
        ?string $buttonlabel = null,
        ?string $buttonurl = null
    ): void {
        global $DB;

        $user = $DB->get_record('user', [
            'id' => $userid,
            'deleted' => 0,
        ], '*', MUST_EXIST);

        CustomUserMail::send(
            $user,
            $subject,
            $messagehtml,
            $buttonlabel,
            $buttonurl
        );

        AdminLog::log(
            'email.custom.sent',
            $userid,
            'user',
            $userid,
            ['subject' => $subject]
        );        
    }

    public static function send_password_reset_notice(
        int $userid,
        string $newpassword
    ): void {
        global $DB;

        $user = $DB->get_record('user', [
            'id' => $userid,
            'deleted' => 0,
        ], '*', MUST_EXIST);

        PasswordResetMail::send($user, $newpassword);

        AdminLog::log(
            'email.password_reset_notice.sent',
            $userid,
            'user',
            $userid
        );
    }
}