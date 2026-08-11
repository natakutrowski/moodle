<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * L7.3A — guards the public Storefront customer-state contract.
 *
 * This deliberately certifies the integration boundaries in addition to
 * read-model behaviour: guest access remains public, guest carts are
 * anonymous/session-backed, and ownership is evaluated only for authenticated
 * non-guest users.
 */
final class commerce_storefront_customer_state_matrix_l73_test extends advanced_testcase {
    public function test_storefront_remains_public_for_guests(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/storefront_product.php'
        );

        self::assertStringContainsString(
            'subscription_config::guard_public_access()',
            $source
        );
        self::assertStringNotContainsString('require_login();', $source);
        self::assertStringContainsString(
            "isloggedin() && !isguestuser() ? (int)\$USER->id : 0",
            $source
        );
    }

    public function test_guest_currency_is_session_backed_but_connected_currency_is_persistent(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/storefront_product.php'
        );

        self::assertStringContainsString(
            '$SESSION->local_subscriptions_storefront_currency = $currency;',
            $source
        );
        self::assertStringContainsString(
            'set_user_preference(',
            $source
        );
        self::assertStringContainsString(
            "isloggedin() && !isguestuser()",
            $source
        );
    }

    public function test_repository_never_treats_guest_as_owner(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/'
                . 'repository/CommerceStorefrontRepository.php'
        );

        self::assertStringContainsString(
            'isloggedin() && !isguestuser()',
            $source
        );
    }

    public function test_owned_and_non_owned_commerce_actions_are_mutually_exclusive(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/'
                . 'presentation/CommerceStorefrontPresenter.php'
        );
        $panel = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_commerce_panel.mustache'
        );

        self::assertStringContainsString(
            "'canpurchase' => !\$owned && \$upgrade === null",
            $presenter
        );
        self::assertStringContainsString(
            '$upgrade = $product->is_owned() ? null : $product->get_upgrade();',
            $presenter
        );

        self::assertStringContainsString('{{#canpurchase}}', $panel);
        self::assertStringContainsString('{{#owned}}', $panel);
        self::assertStringContainsString('{{^owned}}', $panel);
        self::assertStringContainsString('{{ownedactionurl}}', $panel);
    }

    public function test_owned_product_does_not_expose_public_purchase_price_panel(): void {
        global $CFG;

        $panel = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_commerce_panel.mustache'
        );

        self::assertMatchesRegularExpression(
            '~\{\{\^owned\}\}\s*\{\{>\s*local_subscriptions/storefront/product_price\s*\}\}\s*\{\{/owned\}\}~',
            $panel
        );
        self::assertMatchesRegularExpression(
            '~\{\{\^owned\}\}\s*\{\{>\s*local_subscriptions/storefront/product_badges\s*\}\}\s*\{\{/owned\}\}~',
            $panel
        );
    }
}
