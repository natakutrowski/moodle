<?php

namespace local_subscriptions\mail;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\mailer;

final class PasswordResetMail {

    public static function send(\stdClass $user, string $newpassword): void {
        $subject = get_string('crm_password_email_subject', 'local_subscriptions');

        $message =
            html_writer::tag('p', get_string('crm_password_email_intro', 'local_subscriptions', fullname($user))) .
            html_writer::tag('p',
                get_string('crm_password_email_password', 'local_subscriptions') . '<br>' .
                html_writer::tag('strong', s($newpassword))
            ) .
            html_writer::tag('p', get_string('crm_password_email_security', 'local_subscriptions'));

        [$html, $text] = MailRenderer::layout(
            $subject,
            $message,
            get_string('crm_login_button', 'local_subscriptions'),
            new moodle_url('/login/index.php')
        );

        mailer::deliver($user, $subject, $html, $text);
    }
}