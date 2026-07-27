<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/** Presents installed Moodle languages consistently in Commerce CRM screens. */
final class CommerceLanguagePresentation {
    /** @var array<string, string> */
    private const FLAGS = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
        'it' => '🇮🇹',
        'es' => '🇪🇸',
        'de' => '🇩🇪',
        'pt' => '🇵🇹',
        'nl' => '🇳🇱',
        'pl' => '🇵🇱',
        'uk' => '🇺🇦',
        'zh' => '🇨🇳',
        'ja' => '🇯🇵',
        'ko' => '🇰🇷',
        'ar' => '🇸🇦',
        'tr' => '🇹🇷',
    ];

    public static function flag(string $language): string {
        $base = self::base_code($language);
        return self::FLAGS[$base] ?? '🌐';
    }

    public static function display_name(string $language, ?string $translatedname = null): string {
        $translatedname = trim((string) $translatedname);

        if ($translatedname !== '') {
            // Remove invisible Unicode formatting marks around the label.
            $translatedname = (string) preg_replace(
                '/^[\p{Cf}\s]+|[\p{Cf}\s]+$/u',
                '',
                $translatedname
            );

            // Moodle language names may end with a locale code such as "(en)".
            $translatedname = (string) preg_replace(
                '/[\p{Cf}\s]*\([a-z]{2,3}(?:[_-][a-z]{2})?\)[\p{Cf}\s]*$/iu',
                '',
                $translatedname
            );

            $translatedname = trim($translatedname);
        }

        return $translatedname !== ''
            ? $translatedname
            : strtoupper(trim($language));
    }

    public static function label(string $language, ?string $translatedname = null): string {
        return self::flag($language) . ' ' . self::display_name($language, $translatedname);
    }

    public static function badge(string $language, ?string $translatedname = null): string {
        return html_writer::span(
            self::label($language, $translatedname),
            'crm-commerce-language-badge',
            ['lang' => s($language)]
        );
    }

    private static function base_code(string $language): string {
        $language = strtolower(trim($language));
        return preg_split('/[_-]/', $language, 2)[0] ?? $language;
    }
}
