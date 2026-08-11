<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_navigation_cleanup_test extends \advanced_testcase {
    public function test_profile_uses_lightweight_customer_links_without_legacy_modal(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lib.php'
        );

        $this->assertStringContainsString(
            'profile_link_courses',
            $source
        );
        $this->assertStringContainsString(
            'profile_link_resources',
            $source
        );
        $this->assertStringContainsString(
            'profile_link_purchases',
            $source
        );
        $this->assertStringNotContainsString(
            'render_user_subscriptions_block(',
            $source
        );
        $this->assertDoesNotMatchRegularExpression(
            '/^[\t ]*local_subscriptions_inject_subscribe_modal\(\$PAGE\);/m',
            $source
        );
    }

    public function test_customer_routes_are_centralised_and_localised(): void {
        $this->assertSame(
            subscription_config::public_route_path('storefront'),
            subscription_config::storefront_page()
        );
        $this->assertSame(
            subscription_config::public_route_path('my_purchases'),
            subscription_config::customer_purchases_page()
        );
        $this->assertSame(
            subscription_config::public_route_path('my_resources'),
            subscription_config::customer_digital_library_page()
        );
    }
}
