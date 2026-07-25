<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\policy;

defined('MOODLE_INTERNAL') || die();

/** Stable identifiers for progressive native-read activation. */
final class CommerceReadConsumer {
    public const CRM = 'crm';
    public const ADMIN = 'admin';
    public const USER = 'user';
    public const EMAIL = 'email';
    public const TASK = 'task';

    public static function all(): array {
        return [self::CRM, self::ADMIN, self::USER, self::EMAIL, self::TASK];
    }
}
