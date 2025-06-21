<?php
// This file is part of local_subscriptions plugin.

defined('MOODLE_INTERNAL') || die();

/**
 * Get the full label for a language (e.g. "Français 🇫🇷").
 *
 * @param string $code ISO language code (e.g. 'fr', 'en').
 * @return string
 */
function local_subscriptions_get_lang_label(string $code): string {
    return local_subscriptions_get_lang_name($code) . ' ' . local_subscriptions_get_lang_flag($code);
}

/**
 * Get the native name of a language.
 *
 * @param string $code ISO language code.
 * @return string
 */
function local_subscriptions_get_lang_name(string $code): string {
    static $names = [
        'fr' => 'Français',
        'en' => 'English',
        'ru' => 'Русский',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ];

    return $names[$code] ?? strtoupper($code);
}

/**
 * Get the emoji flag for a language.
 *
 * @param string $code ISO language code.
 * @return string
 */
function local_subscriptions_get_lang_flag(string $code): string {
    static $flags = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
        'es' => '🇪🇸',
        'de' => '🇩🇪',
        'it' => '🇮🇹',
    ];

    return $flags[$code] ?? '🌐';
}
