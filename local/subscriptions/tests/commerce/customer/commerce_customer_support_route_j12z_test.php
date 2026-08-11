<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\CommerceRouteRegistry;
use local_subscriptions\url\UrlFactory;

final class commerce_customer_support_route_j12z_test extends \advanced_testcase {
    public function test_support_route_is_localised(): void {
        self::assertSame(
            '/aide',
            subscription_config::public_route_path('support', 'fr')
        );
        self::assertSame(
            '/support',
            subscription_config::public_route_path('support', 'en')
        );
        self::assertSame(
            '/podderzhka',
            subscription_config::public_route_path('support', 'ru')
        );
        self::assertSame('support', CommerceRouteRegistry::SUPPORT);
        self::assertSame(
            'support_request.php',
            CommerceRouteRegistry::target(CommerceRouteRegistry::SUPPORT)
        );
    }

    public function test_url_factory_preserves_order_context(): void {
        $url = UrlFactory::support_for_order(
            'cmp_demo',
            ['source' => 'order']
        );

        self::assertStringEndsWith(
            subscription_config::public_route_path('support'),
            $url->get_path()
        );
        self::assertSame(
            'cmp_demo',
            $url->get_param('reference')
        );
        self::assertSame(
            'order',
            $url->get_param('source')
        );
    }

    public function test_customer_surfaces_do_not_render_technical_support_url(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions';
        $files = [
            $root . '/order_result.php',
            $root . '/order_details.php',
            $root . '/support_request.php',
            $root
                . '/classes/commerce/customer/hub/'
                . 'CommerceCustomerHubService.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            self::assertIsString($source);
            self::assertStringNotContainsString(
                '/local/subscriptions/support_request.php',
                $source,
                $file
            );
        }
    }

    public function test_public_url_audit_covers_support_controller(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/cli/commerce/'
                . 'audit_public_urls.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '|support_request)',
            $source
        );
        self::assertStringNotContainsString(
            "'/support_request.php',",
            $source
        );
    }
}
