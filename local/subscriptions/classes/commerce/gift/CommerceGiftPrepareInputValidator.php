<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Form-level validation for the G5B.1 preview. This class does not grant
 * rights, write to Moodle, create a purchase or issue a trusted gift draft.
 */
final class CommerceGiftPrepareInputValidator {
    /**
     * @param array<string,string> $raw
     * @return array{values:array<string,string>,errors:list<string>,scheduledat:?int}
     */
    public static function validate(array $raw, int $now): array {
        $keys = ['recipientname', 'recipientemail', 'deliverymode', 'personalmessage',
            'locale', 'schedule', 'deliverydate', 'currency'];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = trim((string)($raw[$key] ?? ''));
        }
        $errors = [];
        $mode = $values['deliverymode'];
        if (!in_array($mode, ['email', 'link'], true)) {
            $errors[] = 'error_mode';
        }
        if ($mode === 'email') {
            $values['recipientemail'] = strtolower($values['recipientemail']);
            if (strlen($values['recipientemail']) > 254 ||
                filter_var($values['recipientemail'], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'error_email';
            }
        } else {
            // No recipient email is carried in a link gift, even if the user
            // switched from email to link and the form retained stale input.
            $values['recipientemail'] = '';
        }
        if (self::length($values['recipientname']) > 80) {
            $errors[] = 'error_name';
        }
        if (self::length($values['personalmessage']) > 500) {
            $errors[] = 'error_message';
        }
        if (!in_array($values['locale'], ['fr', 'en', 'ru'], true)) {
            $errors[] = 'error_locale';
        }
        if (!in_array($values['currency'], ['EUR', 'RUB'], true)) {
            $errors[] = 'error_currency';
        }
        $time = null;
        if (!in_array($values['schedule'], ['now', 'later'], true)) {
            $errors[] = 'error_date';
        } else if ($values['schedule'] === 'later') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',
                $values['deliverydate'], new \DateTimeZone('Europe/Paris'));
            if (!$date || $date->format('Y-m-d\TH:i') !== $values['deliverydate'] ||
                $date->getTimestamp() < $now + 600 || $date->getTimestamp() > $now + 60 * 86400) {
                $errors[] = 'error_date';
            } else {
                $time = $date->getTimestamp();
            }
        }
        return ['values' => $values, 'errors' => $errors, 'scheduledat' => $time];
    }

    private static function length(string $value): int {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }
        return preg_match_all('/./us', $value) ?: 0;
    }
}
