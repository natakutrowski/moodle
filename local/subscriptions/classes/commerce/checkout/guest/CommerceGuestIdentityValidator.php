<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Validates and normalises identity data submitted by a Guest Checkout customer. */
final class CommerceGuestIdentityValidator {
    private const EMAIL_MAX_LENGTH = 100;
    private const NAME_MAX_LENGTH = 100;

    /**
     * @return array{email: string, firstname: string, lastname: string}
     */
    public static function validate(string $email, string $firstname, string $lastname): array {
        $email = \core_text::strtolower(trim($email));
        $firstname = self::normalise_name($firstname);
        $lastname = self::normalise_name($lastname);

        if ($email === ''
                || \core_text::strlen($email) > self::EMAIL_MAX_LENGTH
                || preg_match('/[\x00-\x1F\x7F]/u', $email)
                || !validate_email($email)) {
            throw new \moodle_exception(
                'commerce_guest_checkout_invalid_email',
                'local_subscriptions'
            );
        }

        if ($firstname === '') {
            throw new \moodle_exception(
                'commerce_guest_checkout_invalid_firstname',
                'local_subscriptions'
            );
        }

        if ($lastname === '') {
            throw new \moodle_exception(
                'commerce_guest_checkout_invalid_lastname',
                'local_subscriptions'
            );
        }

        return [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }

    private static function normalise_name(string $value): string {
        $value = clean_param($value, PARAM_NOTAGS);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        if (\core_text::strlen($value) > self::NAME_MAX_LENGTH
                || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
            return '';
        }

        return $value;
    }
}
