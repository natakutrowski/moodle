<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailStatus;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * Presentation helpers for the transactional mail administration screens.
 */
final class CommerceMailAdminPresentation {

    /**
     * @return array<string, string>
     */
    public static function status_options(): array {
        $options = [];

        foreach (CommerceMailStatus::all() as $status) {
            $options[$status] = self::status_label($status);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function type_options(): array {
        $options = [];

        foreach (CommerceMailType::all() as $type) {
            $options[$type] = self::type_label($type);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function language_options(): array {
        return [
            'fr' => self::language_label('fr'),
            'en' => self::language_label('en'),
            'ru' => self::language_label('ru'),
        ];
    }

    public static function status_label(string $status): string {
        $key = 'commerce_mail_status_' . strtolower(trim($status));

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    public static function type_label(string $type): string {
        $key = 'commerce_mail_type_' . strtolower(trim($type));

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return ucfirst(str_replace('_', ' ', $type));
    }

    public static function status_badge_class(string $status): string {
        return match ($status) {
            CommerceMailStatus::QUEUED => 'text-bg-info',
            CommerceMailStatus::PROCESSING => 'text-bg-primary',
            CommerceMailStatus::SENT => 'text-bg-success',
            CommerceMailStatus::FAILED => 'text-bg-danger',
            CommerceMailStatus::CANCELLED => 'text-bg-secondary',
            default => 'text-bg-light',
        };
    }

    public static function type_badge_class(string $type): string {
        return match ($type) {
            CommerceMailType::PURCHASE_ACCESS => 'text-bg-success',
            CommerceMailType::GRANT_ACCESS => 'text-bg-success',
            CommerceMailType::PURCHASE_RECEIPT => 'text-bg-primary',
            CommerceMailType::PAYMENT_PENDING => 'text-bg-warning',
            CommerceMailType::PAYMENT_FAILED => 'text-bg-danger',
            CommerceMailType::PAYMENT_CANCELLED => 'text-bg-secondary',
            CommerceMailType::ACCOUNT_ACTIVATION => 'text-bg-info',
            CommerceMailType::PERSONAL_OFFER => 'text-bg-dark',
            CommerceMailType::TRIAL_WELCOME => 'text-bg-info',
            default => 'text-bg-light',
        };
    }

    public static function language_label(string $language): string {
        $language = strtolower(trim($language));

        $flag = match ($language) {
            'fr', 'fr_fr' => '🇫🇷',
            'en', 'en_us', 'en_gb' => '🇬🇧',
            'ru', 'ru_ru' => '🇷🇺',
            default => '🌐',
        };

        $key = 'commerce_mail_language_' . substr($language, 0, 2);
        $label = get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : strtoupper($language);

        return $flag . ' ' . $label;
    }

    private function __construct() {
    }
}
