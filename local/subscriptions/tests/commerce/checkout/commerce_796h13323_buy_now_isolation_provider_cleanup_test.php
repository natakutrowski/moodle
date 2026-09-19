<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13323_buy_now_isolation_provider_cleanup_test extends \advanced_testcase {
    public function test_buy_now_no_longer_clears_normal_cart(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        $start = strpos(
            $source,
            "if (\$action === 'buynow')"
        );
        self::assertNotFalse($start);

        $end = strpos(
            $source,
            "} else if (\$action === 'remove')",
            $start
        );
        self::assertNotFalse($end);

        $block = substr(
            $source,
            $start,
            $end - $start
        );

        self::assertStringContainsString(
            'prepare_direct_product(',
            $block
        );
        self::assertStringContainsString(
            'CommerceDirectPurchaseSession::store(',
            $block
        );
        self::assertStringNotContainsString(
            'clear_cart(',
            $block
        );
    }

    public function test_direct_checkout_uses_transient_product_snapshot(): void {
        global $CFG;

        $runtime = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/'
            . 'CommerceCheckoutRuntime.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/cart/service/'
            . 'CommerceCartService.php'
        );

        self::assertStringContainsString(
            'direct_snapshot(',
            $runtime
        );
        self::assertStringContainsString(
            'prepare_direct_product(',
            $service
        );
        self::assertStringContainsString(
            'bool $persist',
            $service
        );
    }

    public function test_buy_now_surfaces_have_no_provider_experience_legacy_modal(): void {
        global $CFG;

        foreach ([
            'templates/storefront/product_card.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/showroom/offer.mustache',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'name="action" value="buynow"',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'data-provider-experience',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'name="express" value="1"',
                $source,
                $relative
            );
        }
    }

    public function test_public_storefront_and_showroom_no_longer_load_provider_experience_dialog(): void {
        global $CFG;

        foreach ([
            'templates/storefront/catalog.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/showroom/third_group_verbs.mustache',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString(
                'checkout/provider_experience',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'local_subscriptions/provider_experience',
                $source,
                $relative
            );
        }
    }

    public function test_cart_action_no_longer_launches_provider_before_checkout(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertStringNotContainsString(
            'CommerceCheckoutExpressService',
            $source
        );
        self::assertStringNotContainsString(
            'providerconfirmed',
            $source
        );
        self::assertStringContainsString(
            'CommercePurchaseFlow::DIRECT',
            $source
        );
    }
}
