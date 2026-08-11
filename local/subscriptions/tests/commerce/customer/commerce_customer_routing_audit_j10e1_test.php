<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_routing_audit_j10e1_test extends \advanced_testcase {
    public function test_core_course_and_profile_routes_redirect_instead_of_include(): void {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/public_router.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            "redirect(new moodle_url('/course/view.php'",
            $source
        );
        self::assertStringContainsString(
            "redirect(new moodle_url('/user/profile.php'",
            $source
        );
        self::assertStringNotContainsString(
            "require(__DIR__ . '/../../course/view.php')",
            $source
        );
    }

    public function test_public_url_audit_distinguishes_infrastructure_fallbacks(): void {
        $source = file_get_contents(
            dirname(__DIR__, 3) . '/cli/commerce/audit_public_urls.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString(
            "'/classes/url/'",
            $source
        );
        self::assertStringContainsString(
            'Intentional technical targets/fallbacks ignored',
            $source
        );
        self::assertStringContainsString(
            'OK: no customer-facing technical URL found.',
            $source
        );
    }
}
