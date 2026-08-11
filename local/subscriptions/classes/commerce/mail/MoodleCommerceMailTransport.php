<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/** Moodle email transport used by the Commerce transactional mail core. */
final class MoodleCommerceMailTransport implements CommerceMailTransport {

    public function send(CommerceMailMessage $message): void {
        $recipient = $this->moodle_recipient($message->get_recipient());
        $sender = \core_user::get_support_user();
        $attachments = $message->get_attachments();
        if (count($attachments) > 1) {
            throw new \RuntimeException('Moodle Commerce mail transport currently supports one attachment per message.');
        }

        $attachmentpath = '';
        $attachmentname = '';
        try {
            if ($attachments !== []) {
                $attachment = $attachments[0];
                $directory = make_temp_directory('local_subscriptions/commerce_mail');
                $attachmentpath = tempnam($directory, 'mail_');
                if ($attachmentpath === false || file_put_contents($attachmentpath, $attachment->get_content()) === false) {
                    throw new \RuntimeException('Could not create the temporary Commerce mail attachment.');
                }
                $attachmentname = $attachment->get_filename();
            }

            $sent = email_to_user(
                $recipient,
                $sender,
                $message->get_subject(),
                $message->get_text(),
                $message->get_html(),
                $attachmentpath,
                $attachmentname
            );

            if ($sent !== true) {
                throw new \RuntimeException(
                    'Moodle could not send the Commerce transactional email to '
                    . $message->get_recipient()->get_email()
                    . '.'
                );
            }
        } finally {
            if ($attachmentpath !== '' && is_file($attachmentpath)) {
                @unlink($attachmentpath);
            }
        }
    }

    private function moodle_recipient(CommerceMailRecipient $recipient): \stdClass {
        global $DB;
        $user = null;
        $userid = $recipient->get_user_id();
        if ($userid !== null) {
            $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
        }
        if (!$user) {
            $user = clone \core_user::get_support_user();
            $user->id = $userid ?? -1;
            $user->email = $recipient->get_email();
            [$firstname, $lastname] = $this->split_name($recipient->get_name());
            $user->firstname = $firstname;
            $user->lastname = $lastname;
        }
        $user->email = $recipient->get_email();
        $user->mailformat = 1;
        $user->maildisplay = 1;
        $user->deleted = 0;
        $user->suspended = 0;
        return $user;
    }

    /** @return array{0:string,1:string} */
    private function split_name(string $name): array {
        $name = trim($name);
        if ($name === '') { return ['CampusFR', 'Customer']; }
        $parts = preg_split('/\s+/u', $name, 2) ?: [];
        return [(string)($parts[0] ?? 'CampusFR'), (string)($parts[1] ?? 'Customer')];
    }
}
