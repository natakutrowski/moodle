<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13164_personal_offer_guest_frictionless_ux_test extends \advanced_testcase {
    public function test_locked_identity_does_not_reappear_as_empty_form(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            '$guestverificationstate->is_locked() !== true',
            $source
        );
        self::assertStringContainsString(
            '$guestsession->get_first_name()',
            $source
        );
        self::assertStringContainsString(
            '$guestsession->get_last_name()',
            $source
        );
    }

    public function test_new_guest_unlocks_payment_in_place(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        $start = strpos(
            $source,
            'const initialisePersonalOfferIdentityCompletion'
        );
        self::assertNotFalse($start);

        $block = substr($source, $start, 6500);

        self::assertStringContainsString(
            'if (payload.requiresLogin)',
            $block
        );
        self::assertStringContainsString(
            'if (payload.paymentReady)',
            $block
        );
        self::assertStringContainsString(
            'setGuestPaymentGate(form, false)',
            $block
        );
        self::assertStringContainsString(
            'wrap.remove()',
            $block
        );
    }

    public function test_personal_offer_gate_copy_mentions_details_not_email_verification(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $fr = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            'commerce_personal_offer_guest_details_gate_hint',
            $checkout
        );
        self::assertStringContainsString(
            'Complétez vos coordonnées pour choisir votre moyen de paiement.',
            $fr
        );
    }
}
