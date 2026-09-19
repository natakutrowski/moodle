<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12752_consent_checkbox_to_lock_test extends \advanced_testcase {
    public function test_checkbox_is_visible_before_acceptance_and_lock_after(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            'commerce-checkout-legal__status-checkbox',
            $template
        );
        self::assertStringContainsString(
            '.commerce-checkout-legal__status-checkbox',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-checkout-legal\[data-checkout-legal-card\]\.is-accepted\s+\.commerce-checkout-legal__status-checkbox\s*\{\s*display:\s*none;/s',
            $css
        );
        self::assertMatchesRegularExpression(
            '/\.commerce-checkout-legal\[data-checkout-legal-card\]\.is-accepted\s+\.commerce-checkout-legal__status-lock\s*\{\s*display:\s*inline-flex;/s',
            $css
        );
    }

    public function test_accepted_check_matches_discreet_action_card_treatment(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString('background: transparent;', $css);
        self::assertStringContainsString('color: var(--bs-primary);', $css);
    }
}
