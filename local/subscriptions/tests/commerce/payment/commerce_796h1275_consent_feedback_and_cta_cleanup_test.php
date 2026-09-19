<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1275_consent_feedback_and_cta_cleanup_test extends \advanced_testcase {
    public function test_generic_submit_hidden_attribute_is_css_enforced(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            '.commerce-checkout-submit[hidden]',
            $css
        );
        self::assertStringContainsString(
            'display: none !important;',
            $css
        );
    }

    public function test_terms_feedback_and_current_accepted_state_are_rendered(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            'data-checkout-terms-feedback',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-legal-check',
            $template
        );
        self::assertStringContainsString(
            'commerce-checkout-legal__status-lock',
            $template
        );
        self::assertStringContainsString(
            '{{termsrequiredlabel}}',
            $template
        );
        self::assertStringContainsString(
            "get_string('commerce_checkout_terms_required'",
            $checkout
        );
    }

    public function test_payment_intent_shows_feedback_and_marks_consent_accepted(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString(
            'showConsentFeedback();',
            $amd
        );
        self::assertStringContainsString(
            'hideConsentFeedback();',
            $amd
        );
        self::assertStringContainsString(
            "legalCard.classList.add(\n                'is-accepted'",
            $amd
        );
        self::assertStringContainsString(
            "legalCard.setAttribute(\n                'aria-checked',\n                'true'",
            $amd
        );
        self::assertStringContainsString(
            'legalCheck.hidden = false;',
            $amd
        );
        self::assertStringContainsString(
            "'campusfr:checkout-consent-accepted'",
            $amd
        );
    }

    public function test_express_wallets_enforce_terms_at_confirmation_without_blocking_disclosure(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringNotContainsString(
            'commerce-checkout-express-wallets__consent-gate',
            $amd
        );
        self::assertStringContainsString(
            "express.on(\n            'confirm'",
            $amd
        );
        self::assertStringContainsString(
            "'[data-checkout-terms]'",
            $amd
        );
        self::assertStringContainsString(
            'legalCard.classList.add(',
            $amd
        );
        self::assertStringContainsString(
            'event?.paymentFailed?.({',
            $amd
        );
    }
}
