<?php

declare(strict_types=1);

namespace local_subscriptions\url;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\support\Region;

/** Resolves stable public legal URLs to the configured regional documents. */
final class CommerceLegalUrlResolver {
    public const TERMS = 'terms';
    public const PRIVACY = 'privacy';

    public static function resolve(string $route): \moodle_url {
        $route = strtolower(trim($route));
        if (!in_array($route, [self::TERMS, self::PRIVACY], true)) {
            throw new \coding_exception('Unknown legal route: ' . $route);
        }

        $urls = Region::policyUrls();
        $target = $route === self::PRIVACY
            ? trim((string)($urls['policy'] ?? ''))
            : trim((string)($urls['terms'] ?? ''));

        if ($target === '') {
            throw new \moodle_exception(
                'commerce_route_not_found',
                'local_subscriptions'
            );
        }

        return new \moodle_url($target);
    }
}
