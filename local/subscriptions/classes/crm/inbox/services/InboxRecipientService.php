<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalises recipient fields used by compose/reply/reply-all/forward.
 */
final class InboxRecipientService {

    /**
     * @return string[]
     */
    public function parse(string|array $value): array {
        $parts = is_array($value)
            ? $value
            : preg_split(
                '/[\s,;]+/',
                trim($value)
            );

        $emails = [];

        foreach ($parts ?: [] as $part) {
            $email = \core_text::strtolower(
                trim((string)$part)
            );

            if ($email === '') {
                continue;
            }

            if (!validate_email($email)) {
                throw new \moodle_exception(
                    'crm_inbox_invalid_recipient_value_o6',
                    'local_subscriptions',
                    '',
                    $email
                );
            }

            $emails[$email] = $email;
        }

        return array_values($emails);
    }

    /**
     * @return array{to:string[],cc:string[],bcc:string[]}
     */
    public function normalize(
        string|array $to,
        string|array $cc = [],
        string|array $bcc = []
    ): array {
        $toemails = $this->parse($to);
        $ccemails = $this->parse($cc);
        $bccemails = $this->parse($bcc);

        $seen = [];

        $dedupe = static function (
            array $emails
        ) use (&$seen): array {
            $result = [];

            foreach ($emails as $email) {
                if (isset($seen[$email])) {
                    continue;
                }

                $seen[$email] = true;
                $result[] = $email;
            }

            return $result;
        };

        return [
            'to' => $dedupe($toemails),
            'cc' => $dedupe($ccemails),
            'bcc' => $dedupe($bccemails),
        ];
    }
}
