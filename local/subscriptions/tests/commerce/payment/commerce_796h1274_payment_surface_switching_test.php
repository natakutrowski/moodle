<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1274_payment_surface_switching_test extends \advanced_testcase {
    public function test_central_intent_allows_prepared_surface_to_consume_resume(): void {
        global $CFG;

        $intent = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString(
            "'campusfr:payment-intent-execute'",
            $intent
        );
        self::assertStringContainsString(
            'cancelable: true',
            $intent
        );
        self::assertStringContainsString(
            'if (!form.dispatchEvent(resumeEvent))',
            $intent
        );
    }

    public function test_stripe_inline_hides_stale_surface_and_reuses_matching_prepared_surface(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        self::assertStringContainsString(
            "preparedMethod === method",
            $amd
        );
        self::assertStringContainsString(
            "event.preventDefault();",
            $amd
        );
        self::assertStringContainsString(
            "panel.hidden = true;",
            $amd
        );
        self::assertStringContainsString(
            "selectedMethod(form)\n                        !== requestedMethod",
            $amd
        );
    }

    public function test_alfa_iframe_resume_reuses_existing_mounted_surface(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            "'campusfr:payment-intent-execute'",
            $amd
        );
        self::assertStringContainsString(
            "method === 'card'",
            $amd
        );
        self::assertStringContainsString(
            'iframeMounted',
            $amd
        );
        self::assertStringContainsString(
            'showPreparedCardSurface()',
            $amd
        );
    }

    public function test_sbp_resume_reuses_qr_and_late_response_cannot_resurface(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );

        self::assertStringContainsString(
            "'campusfr:payment-intent-execute'",
            $amd
        );
        self::assertStringContainsString(
            "method === 'sbp'",
            $amd
        );
        self::assertStringContainsString(
            'schedulePolling();',
            $amd
        );
        self::assertStringContainsString(
            "const stillSelected =",
            $amd
        );
        self::assertStringContainsString(
            "selectedMethod(form)\n                === 'sbp'",
            $amd
        );
    }

    public function test_express_wallet_missing_consent_guides_user_without_fake_auto_resume(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "'[data-checkout-terms]'",
            $amd
        );
        self::assertStringContainsString(
            "'[data-checkout-legal-card]'",
            $amd
        );
        self::assertStringContainsString(
            "legalCard.classList.add(\n                            'is-required'",
            $amd
        );
        self::assertStringContainsString(
            'event?.paymentFailed?.({',
            $amd
        );
    }
}
