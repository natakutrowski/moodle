<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a51_guest_activation_and_processing_splash_test extends \advanced_testcase {
    public function test_guest_activation_uses_current_token_and_owned_order(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_account_activation_start.php'
        );

        self::assertStringContainsString(
            '$repository->find_by_token($token)',
            $source
        );
        self::assertStringNotContainsString(
            '$repository->find_by_purchase_reference($reference)',
            $source
        );
        self::assertStringContainsString(
            'CommerceOrderPresentationService::create()->find_for_guest_session(',
            $source
        );
        self::assertStringContainsString(
            '$session->get_purchase_reference()',
            $source
        );
        self::assertStringContainsString(
            "'resume_purchase_reference'",
            $source
        );
        self::assertStringContainsString(
            '$session->is_expired()',
            $source
        );
    }

    public function test_processing_splash_has_distinct_copy_from_preparation(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_splash.js'
        );

        self::assertStringContainsString(
            'data-processing-title="{{paymentprocessinglabel}}"',
            $template
        );
        self::assertStringContainsString(
            'data-processing-message="{{paymentprocessingmessage}}"',
            $template
        );
        self::assertStringContainsString(
            "resolvedState === 'processing'",
            $amd
        );
        self::assertStringContainsString(
            'title.dataset.processingTitle',
            $amd
        );
        self::assertStringContainsString(
            'message.dataset.processingMessage',
            $amd
        );
    }

    public function test_stripe_confirmation_already_uses_processing_state(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        self::assertStringContainsString(
            "showPaymentSplash(\n                    'processing'",
            $amd
        );
    }

    public function test_processing_copy_exists_in_all_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                'commerce_checkout_payment_processing',
                $source
            );
            self::assertStringContainsString(
                'commerce_checkout_payment_processing_message',
                $source
            );
        }
    }
}
