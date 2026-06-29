<?php

namespace local_subscriptions\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\mailer;

final class CustomUserMail {

    public static function send(
        \stdClass $user,
        string $subject,
        string $messagehtml,
        ?string $buttonlabel = null,
        ?string $buttonurl = null
    ): void {
        $subject = trim($subject);
        $buttonlabel = trim((string)$buttonlabel);
        $buttonurl = trim((string)$buttonurl);

        if ($subject === '') {
            throw new \moodle_exception('crm_email_subject_required', 'local_subscriptions');
        }

        if (trim(strip_tags($messagehtml)) === '') {
            throw new \moodle_exception('crm_email_body_required', 'local_subscriptions');
        }

        [$html, $text] = MailRenderer::layout(
            $subject,
            format_text($messagehtml, FORMAT_HTML),
            $buttonlabel !== '' ? $buttonlabel : null,
            $buttonurl !== '' ? $buttonurl : null
        );

        mailer::deliver($user, $subject, $html, $text);
    }
}