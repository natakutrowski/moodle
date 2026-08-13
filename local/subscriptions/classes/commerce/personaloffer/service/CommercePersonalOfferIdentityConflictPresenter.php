<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\service;

defined('MOODLE_INTERNAL') || die();

/** Public-safe presentation helpers for Personal Offer identity conflicts. */
final class CommercePersonalOfferIdentityConflictPresenter {
    public static function mask_email(string $email): string {
        $email = strtolower(trim($email));
        if (!validate_email($email)) {
            return '';
        }
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(3, strlen($local)));
        return $visible . str_repeat('•', max(3, min(8, strlen($local) - strlen($visible)))) . '@' . $domain;
    }
}
