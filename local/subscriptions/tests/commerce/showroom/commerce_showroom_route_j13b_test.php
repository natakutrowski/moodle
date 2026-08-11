<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomUrl;
use local_subscriptions\url\CommerceRouteRegistry;
use local_subscriptions\url\UrlFactory;

final class commerce_showroom_route_j13b_test extends \advanced_testcase {
    public function test_showroom_route_targets_public_controller(): void {
        self::assertSame('showroom.php', CommerceRouteRegistry::target(
            CommerceRouteRegistry::SHOWROOM
        ));
    }

    public function test_showroom_urls_are_localised(): void {
        $key = CommerceShowroomRegistry::THIRD_GROUP_VERBS;
        self::assertStringEndsWith(
            '/verbes-3e-groupe',
            CommerceShowroomUrl::make($key, [], 'fr')->get_path()
        );
        self::assertStringEndsWith(
            '/third-group-verbs',
            UrlFactory::showroom($key, [], 'en')->get_path()
        );
        self::assertStringEndsWith(
            '/glagoly-tretey-gruppy',
            UrlFactory::showroom($key, [], 'ru')->get_path()
        );
    }
}
