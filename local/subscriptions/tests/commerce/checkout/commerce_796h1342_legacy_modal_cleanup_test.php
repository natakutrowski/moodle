<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1342_legacy_modal_cleanup_test extends \advanced_testcase {
    public function test_legacy_provider_experience_assets_are_gone(): void {
        global $CFG;

        foreach ([
            'templates/checkout/provider_experience.mustache',
            'amd/src/provider_experience.js',
            'amd/build/provider_experience.min.js',
        ] as $relative) {
            self::assertFileDoesNotExist(
                $CFG->dirroot . '/local/subscriptions/' . $relative,
                $relative
            );
        }
    }

    public function test_public_purchase_surfaces_cannot_reload_legacy_provider_experience(): void {
        global $CFG;

        foreach ([
            'templates/storefront/catalog.mustache',
            'templates/storefront/product_card.mustache',
            'templates/storefront/product_commerce_panel.mustache',
            'templates/showroom/offer.mustache',
            'templates/showroom/third_group_verbs.mustache',
            'templates/checkout/page.mustache',
            'amd/src/showroom.js',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString(
                'data-provider-experience',
                $source,
                $relative
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

    public function test_current_checkout_payment_surfaces_are_preserved(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $alfa = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'data-checkout-payment-splash',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-alfa-iframe',
            $alfa
        );
        self::assertStringContainsString(
            'data-checkout-payment-form',
            $template
        );
    }

    public function test_legacy_provider_language_contract_is_gone(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            self::assertStringNotContainsString(
                'commerce_provider_experience_',
                $source,
                $lang
            );
            self::assertStringNotContainsString(
                'commerce_provider_currency_',
                $source,
                $lang
            );
        }
    }
}
