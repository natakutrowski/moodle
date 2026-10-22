<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\CommerceProductSlugService;

/**
 * Stores the optional short public alias of a Showroom in settingsjson.
 *
 * The alias is intentionally separate from the language-specific canonical
 * slugs. It is a convenience/marketing route (for example /a1) that redirects
 * temporarily to the canonical slug matching the visitor language.
 */
final class CommerceShowroomAliasConfig {
    private const ROOT_KEY = 'routing';
    private const SHORT_ALIAS_KEY = 'shortalias';

    public static function from_settings_json(string $settingsjson): string {
        $settings = json_decode($settingsjson, true);
        if (!is_array($settings)) {
            return '';
        }

        $routing = $settings[self::ROOT_KEY] ?? [];
        if (!is_array($routing)) {
            return '';
        }

        return self::clean((string)($routing[self::SHORT_ALIAS_KEY] ?? ''));
    }

    public static function merge_into_settings_json(
        string $settingsjson,
        string $alias
    ): string {
        $settings = json_decode($settingsjson, true);
        if (!is_array($settings)) {
            $settings = [];
        }

        $routing = $settings[self::ROOT_KEY] ?? [];
        if (!is_array($routing)) {
            $routing = [];
        }

        $alias = self::clean($alias);
        if ($alias === '') {
            unset($routing[self::SHORT_ALIAS_KEY]);
        } else {
            $routing[self::SHORT_ALIAS_KEY] = $alias;
        }

        if ($routing === []) {
            unset($settings[self::ROOT_KEY]);
        } else {
            $settings[self::ROOT_KEY] = $routing;
        }

        return json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );
    }

    public static function clean(string $alias): string {
        return CommerceProductSlugService::clean($alias);
    }
}
