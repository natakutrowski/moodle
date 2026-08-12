<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\url\CommerceCustomerProfileRouteResolver;

/**
 * Regression for /mon-profil?id=... admin routing.
 */
final class commerce_customer_profile_admin_route_m43a_test extends advanced_testcase {
    public function test_regular_customer_cannot_redirect_to_another_profile(): void {
        self::assertSame(
            2,
            CommerceCustomerProfileRouteResolver::resolve(
                2,
                115,
                false
            )
        );
    }

    public function test_crm_admin_preserves_explicit_profile_target(): void {
        self::assertSame(
            115,
            CommerceCustomerProfileRouteResolver::resolve(
                2,
                115,
                true
            )
        );
    }

    public function test_no_explicit_target_keeps_current_user(): void {
        self::assertSame(
            2,
            CommerceCustomerProfileRouteResolver::resolve(
                2,
                0,
                true
            )
        );
    }

    public function test_public_router_uses_safe_profile_resolver(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/public_router.php'
        );

        self::assertStringContainsString(
            "optional_param('id', 0, PARAM_INT)",
            $source
        );
        self::assertStringContainsString(
            'Capabilities::can_view_users(',
            $source
        );
        self::assertStringContainsString(
            'CommerceCustomerProfileRouteResolver::resolve(',
            $source
        );
    }
}
