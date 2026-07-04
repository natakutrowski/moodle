<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\mail\CustomUserMail;
use local_subscriptions\mail\PasswordResetMail;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminEvents;

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
            AdminEvents::EMAIL_CUSTOM_SENT,
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
            AdminEvents::EMAIL_PASSWORD_RESET_NOTICE_SENT,
            $userid,
            'user',
            $userid
        );
    }
}