<?php

declare(strict_types=1);

namespace local_subscriptions\url;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the public profile route target.
 *
 * Customer-facing users remain locked to their own profile. CRM/site
 * administrators who may inspect users can preserve an explicit ?id= target.
 */
final class CommerceCustomerProfileRouteResolver {
    public static function resolve(
        int $currentuserid,
        int $requesteduserid,
        bool $canviewotherusers
    ): int {
        if ($currentuserid <= 0) {
            throw new \coding_exception(
                'A current authenticated user is required for profile routing.'
            );
        }

        if ($requesteduserid <= 0) {
            return $currentuserid;
        }

        if ($requesteduserid === $currentuserid) {
            return $currentuserid;
        }

        return $canviewotherusers
            ? $requesteduserid
            : $currentuserid;
    }
}
