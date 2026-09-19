<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129_final_certification_test extends \advanced_testcase {
    public function test_guest_identity_verification_contract_is_present(): void {
        global $CFG;

        $state = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityVerificationState.php'
        );
        $otp = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestIdentityOtpChallengeService.php'
        );

        self::assertStringContainsString("EDITING = 'editing'", $state);
        self::assertStringContainsString("OTP_PENDING = 'otp_pending'", $state);
        self::assertStringContainsString("IDENTITY_LOCKED = 'identity_locked'", $state);
        self::assertStringContainsString('password_hash(', $otp);
        self::assertStringContainsString('password_verify(', $otp);
        self::assertStringContainsString('cancel_queued_by_prefix(', $otp);
    }

    public function test_guest_payment_execution_is_server_gated(): void {
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
        self::assertStringNotContainsString(
            'CommerceGuestCheckoutService::create()->identify(',
            $action
        );
    }

    public function test_existing_account_inline_login_uses_verified_session_identity(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/ajax/guest_existing_account_login.php'
        );
        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_existing_account_login.js'
        );

        self::assertStringContainsString(
            'CommerceGuestIdentityVerificationState::from_session(',
            $endpoint
        );
        self::assertStringContainsString(
            'authenticate_user_login(',
            $endpoint
        );
        self::assertStringContainsString(
            'complete_user_login($user);',
            $endpoint
        );
        self::assertStringContainsString(
            'invalid_credentials',
            $amd
        );
    }

    public function test_guest_cart_continuity_is_centralized(): void {
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
        }

        $cartaction = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );

        self::assertStringContainsString(
            '$cartcustomerresolver->synchronize(',
            $cartaction
        );
        self::assertStringContainsString(
            "'guest_cart_snapshot'",
            $resolver
        );
        self::assertStringContainsString(
            "'guest_cart_authoritative_customerid'",
            $resolver
        );
    }

    public function test_authenticated_cart_reconciliation_uses_canonical_cart_rules(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceAuthenticatedCartReconciliationService.php'
        );
        $resume = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );

        self::assertStringContainsString('->add_product(', $source);
        self::assertStringContainsString(
            "unset(\$metadata['guest_cart_snapshot']);",
            $resume
        );
    }

    public function test_stripe_express_checkout_contract_is_certified(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );
        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        foreach (['apple_pay', 'google_pay', 'link', 'klarna'] as $method) {
            self::assertStringContainsString("'{$method}'", $amd);
        }

        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'card';",
            $gateway
        );
        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'link';",
            $gateway
        );
        self::assertStringContainsString(
            "\$paymentmethodtypes[] = 'klarna';",
            $gateway
        );
        self::assertStringContainsString(
            '$embeddedmarketcountry',
            $action
        );
    }

    public function test_no_h129_runtime_test_or_debug_debris_remains(): void {
        global $CFG;

        foreach ([
            'commerce_checkout.php',
            'commerce_checkout_action.php',
            'cart.php',
            'cart_action.php',
            'guest_checkout_resume.php',
            'amd/src/guest_checkout_security.js',
            'amd/src/checkout_express_wallets.js',
            'amd/src/checkout_paypal_embedded.js',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringNotContainsString('testfulfillmentdelay', $source, $relative);
            self::assertStringNotContainsString('console.log(', $source, $relative);
            self::assertStringNotContainsString('NOTIFY_NOTICE', $source, $relative);
            self::assertStringNotContainsString('html_writer::section(', $source, $relative);
        }
    }

    public function test_relevant_guest_checkout_strings_exist_in_fr_en_ru(): void {
        global $CFG;

        $keys = [];
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
            $keys[$lang] = array_fill_keys($matches[1], true);
        }

        $prefixes = [
            'commerce_guest_identity_',
            'commerce_guest_checkout_',
            'commerce_guest_payment_gate_',
            'commerce_guest_cart_',
            'commerce_checkout_existing_account_',
        ];

        foreach (array_keys($keys['fr']) as $key) {
            $relevant = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $relevant = true;
                    break;
                }
            }

            if (!$relevant) {
                continue;
            }

            self::assertArrayHasKey($key, $keys['en'], $key);
            self::assertArrayHasKey($key, $keys['ru'], $key);
        }
    }
}
