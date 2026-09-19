<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1273_payment_intent_and_consent_state_test extends \advanced_testcase {
    public function test_checkout_has_no_implicit_recommended_payment_selection(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            'becomes selected exclusively after an explicit customer action',
            $checkout
        );
        self::assertStringNotContainsString(
            ": (\$paymentpolicy->get_recommended_method() ?? '')",
            $checkout
        );
        self::assertStringContainsString(
            "? \$requestedmethod\n    : '';",
            $checkout
        );
    }

    public function test_action_card_intent_waits_for_consent_then_executes(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString(
            "let pendingMethod = '';",
            $amd
        );
        self::assertStringContainsString(
            'const requestConsent = method => {',
            $amd
        );
        self::assertStringContainsString(
            'pendingMethod = method;',
            $amd
        );
        self::assertStringContainsString(
            'if (pendingMethod !== \'\') {',
            $amd
        );
        self::assertStringContainsString(
            'execute(',
            $amd
        );
        self::assertStringContainsString(
            'form.requestSubmit(',
            $amd
        );
    }

    public function test_consent_is_locked_but_remains_submittable(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString(
            "terms.dataset.consentLocked =\n                '1';",
            $amd
        );
        self::assertStringContainsString(
            "terms.setAttribute(\n                'aria-disabled',\n                'true'",
            $amd
        );
        self::assertStringNotContainsString(
            'terms.disabled = true',
            $amd
        );
        self::assertStringContainsString(
            "legalCard.classList.add(\n                'is-accepted'",
            $amd
        );
    }

    public function test_clicking_terms_without_payment_intent_never_submits(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        $change = strpos(
            $amd,
            "terms.addEventListener(\n            'change'"
        );
        $pending = strpos(
            $amd,
            "if (pendingMethod !== '')",
            $change
        );

        self::assertNotFalse($change);
        self::assertNotFalse($pending);
        self::assertGreaterThan($change, $pending);
    }

    public function test_alfa_card_no_longer_owns_terms_auto_submit(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringNotContainsString(
            "terms.addEventListener(\n            'change'",
            $amd
        );
        self::assertStringContainsString(
            'syncPaymentSurface();',
            $amd
        );
    }
}
