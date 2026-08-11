<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\seo;

defined('MOODLE_INTERNAL') || die();

/**
 * Request-local registry for Storefront head markup.
 *
 * The value is populated before $OUTPUT->header() and consumed by Moodle's
 * official before_standard_head_html_generation hook.
 */
final class CommerceStorefrontSeoHeadRegistry {
    private static string $html = '';

    public static function set(string $html): void {
        self::$html = trim($html);
    }

    public static function get(): string {
        return self::$html;
    }

    public static function clear(): void {
        self::$html = '';
    }
}
