<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

final class HelpContext {

    public const DASHBOARD = 'dashboard';
    public const USER_EXPLORER = 'user_explorer';
    public const USER_PROFILE = 'user_profile';
    public const DIGITAL_PURCHASES = 'digital_purchases';
    public const AUTOMATIONS = 'automations';
    public const COMMAND_CENTER = 'command_center';
    public const INTELLIGENCE = 'intelligence';
    public const EMAIL = 'email';
    public const GENERAL = 'general';

    public static function allowed(): array {
        return [
            self::DASHBOARD,
            self::USER_EXPLORER,
            self::USER_PROFILE,
            self::DIGITAL_PURCHASES,
            self::AUTOMATIONS,
            self::COMMAND_CENTER,
            self::INTELLIGENCE,
            self::EMAIL,
            self::GENERAL,
        ];
    }

    public static function normalize(string $context): string {
        return in_array($context, self::allowed(), true)
            ? $context
            : self::GENERAL;
    }
}