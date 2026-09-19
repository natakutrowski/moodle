<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a44_legal_consent_visual_state_restore_test extends \advanced_testcase {
    public function test_legal_card_contains_preconsent_checkbox_lock_and_discreet_check(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString('commerce-checkout-legal__status-checkbox', $template);
        self::assertStringContainsString('commerce-checkout-legal__status-lock', $template);
        self::assertStringContainsString('commerce-checkout-legal__accepted-check', $template);
        self::assertStringContainsString('data-checkout-legal-check', $template);
        self::assertStringContainsString('data-checkout-terms-feedback', $template);
        self::assertStringContainsString('class="form-check-input visually-hidden"', $template);
    }

    public function test_payment_intent_still_locks_consent_one_way(): void {
        global $CFG;
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );

        self::assertStringContainsString("terms.dataset.consentLocked =\n                '1';", $amd);
        self::assertStringContainsString("legalCard.classList.add(\n                'is-accepted'", $amd);
        self::assertStringContainsString("legalCheck.hidden = false;", $amd);
    }

    public function test_existing_css_switches_checkbox_to_lock_after_acceptance(): void {
        global $CFG;
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString('.commerce-checkout-legal__status-checkbox', $css);
        self::assertStringContainsString('.commerce-checkout-legal__status-lock', $css);
        self::assertStringContainsString('.commerce-checkout-legal__accepted-check', $css);
        self::assertStringContainsString(
            ".commerce-checkout-legal[data-checkout-legal-card].is-accepted\n    .commerce-checkout-legal__status-checkbox",
            $css
        );
    }

    public function test_required_feedback_label_is_exposed_to_template(): void {
        global $CFG;
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            "'termsrequiredlabel' => get_string('commerce_checkout_terms_required', 'local_subscriptions')",
            $checkout
        );
    }
}
