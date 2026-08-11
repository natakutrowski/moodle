<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomTrackingContext;

final class commerce_showroom_commerce_integration_j13c_test extends \advanced_testcase {
    public function test_currency_resolver_honours_explicit_and_stored_values(): void {
        self::assertSame('USD', CommerceShowroomCurrencyResolver::resolve(['EUR', 'USD'], 'usd', 'EUR'));
        self::assertSame('EUR', CommerceShowroomCurrencyResolver::resolve(['EUR', 'USD'], '', 'eur'));
    }

    public function test_tracking_metadata_is_canonical(): void {
        self::assertSame(
            [
                'source' => 'showroom',
                'showroom' => 'third-group-verbs',
                'showroom_offer' => 'bundle',
            ],
            CommerceShowroomTrackingContext::metadata('Third Group Verbs', 'Bundle')
        );
    }

    public function test_showroom_offer_posts_buy_now_and_attribution(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );
        self::assertIsString($source);
        self::assertStringContainsString('name="action" value="buynow"', $source);
        self::assertStringContainsString('name="source" value="showroom"', $source);
        self::assertStringContainsString('name="showroomoffer" value="{{role}}"', $source);
        self::assertStringContainsString('{{#canaccess}}', $source);
    }

    public function test_cart_action_accepts_active_currencies_and_showroom_role(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        self::assertIsString($source);
        self::assertStringContainsString('CommerceShowroomCurrencyResolver::active_currencies($DB)', $source);
        self::assertStringContainsString("optional_param('showroomoffer'", $source);
        self::assertStringContainsString('CommerceShowroomTrackingContext::metadata', $source);
        self::assertStringNotContainsString("['EUR', 'RUB']", $source);
    }
}
