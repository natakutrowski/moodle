<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a8_static_closure_audit_test extends \advanced_testcase {
    public function test_no_temporary_h129_runtime_hooks_remain(): void {
        global $CFG;

        $files = [
            'commerce_checkout.php',
            'commerce_checkout_action.php',
            'cart.php',
            'cart_action.php',
            'guest_checkout_resume.php',
            'ajax/guest_identity_otp_start.php',
            'ajax/guest_identity_otp_verify.php',
            'ajax/guest_existing_account_login.php',
            'amd/src/guest_checkout_security.js',
            'amd/src/checkout_express_wallets.js',
            'amd/src/checkout_paypal_embedded.js',
        ];

        foreach ($files as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString('testfulfillmentdelay', $source, $relative);
            self::assertStringNotContainsString('console.log(', $source, $relative);
            self::assertStringNotContainsString('NOTIFY_NOTICE', $source, $relative);
            self::assertStringNotContainsString('html_writer::section(', $source, $relative);
        }
    }

    public function test_public_cart_surfaces_share_guest_cart_customer_resolver(): void {
        global $CFG;

        foreach ([
            'cart.php',
            'cart_action.php',
            'cart_print.php',
            'digital_catalog.php',
            'storefront_product.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'CommerceGuestCartCustomerResolver',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'CommerceGuestCartCustomerResolver::create()->resolve($currency)',
                $source,
                $relative
            );
            self::assertStringNotContainsString(
                'isloggedin() && !isguestuser() ? (int)$USER->id : 0',
                $source,
                $relative
            );
        }
    }

    public function test_authenticated_one_click_eligibility_intentionally_excludes_guest_provisional_identity(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/ajax/checkout_express_eligibility.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/express/'
            . 'CommerceCheckoutExpressService.php'
        );

        self::assertStringContainsString(
            'isloggedin() && !isguestuser() ? (int)$USER->id : 0',
            $endpoint
        );
        self::assertStringContainsString(
            'if (!isloggedin() || isguestuser() || $userid <= 0)',
            $service
        );
        self::assertStringContainsString(
            "'stable_account_required'",
            $service
        );
    }

    public function test_stripe_embedded_gateway_keeps_deferred_elements_contract(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );
        $action = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            'foreach ($requestedembeddedmethods as $embeddedmethod)',
            $gateway
        );
        self::assertStringContainsString("\$paymentmethodtypes[] = 'card';", $gateway);
        self::assertStringContainsString("\$paymentmethodtypes[] = 'link';", $gateway);
        self::assertStringContainsString("\$paymentmethodtypes[] = 'klarna';", $gateway);
        self::assertStringContainsString('$embeddedmarketcountry', $action);
    }

    public function test_guest_payment_gate_remains_server_authoritative(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            'CommerceGuestPaymentGate::is_ready(',
            $action
        );
        self::assertStringContainsString(
            'guest_identity_verification_required',
            $action
        );
    }

    public function test_guest_cart_mutations_keep_durable_snapshot_synchronized(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );

        self::assertStringContainsString(
            '$cartcustomerresolver->synchronize(',
            $action
        );
        self::assertStringContainsString("'guest_cart_snapshot'", $resolver);
        self::assertStringContainsString("'guest_cart_authoritative_customerid'", $resolver);
    }

    public function test_guest_checkout_strings_are_complete_in_fr_en_ru(): void {
        global $CFG;

        $languages = [];
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );
            preg_match_all(
                '/\$string\[\'([^\']+)\'\]/',
                $source,
                $matches
            );
            $languages[$lang] = array_fill_keys($matches[1], true);
        }

        $prefixes = [
            'commerce_guest_identity_',
            'commerce_guest_checkout_',
            'commerce_guest_payment_gate_',
            'commerce_guest_cart_',
            'commerce_checkout_existing_account_',
        ];

        $reference = [];
        foreach (array_keys($languages['fr']) as $key) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $reference[$key] = true;
                    break;
                }
            }
        }

        foreach (array_keys($reference) as $key) {
            self::assertArrayHasKey($key, $languages['en'], $key);
            self::assertArrayHasKey($key, $languages['ru'], $key);
        }
    }
}
