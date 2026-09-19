<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodCatalogue;
use local_subscriptions\commerce\payment\method\CommercePaymentMethodDefinition;

final class commerce_796h1271_payment_action_card_presentation_model_test extends \advanced_testcase {
    public function test_catalogue_declares_primary_and_secondary_payment_action_cards(): void {
        self::assertSame(
            CommercePaymentMethodDefinition::PRESENTATION_PRIMARY,
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::CARD
            )->get_presentation()
        );
        self::assertSame(
            CommercePaymentMethodDefinition::PRESENTATION_PRIMARY,
            CommercePaymentMethodCatalogue::get(
                CommercePaymentMethod::PAYPAL
            )->get_presentation()
        );

        foreach ([
            CommercePaymentMethod::APPLE_PAY,
            CommercePaymentMethod::GOOGLE_PAY,
            CommercePaymentMethod::LINK,
            CommercePaymentMethod::KLARNA,
            CommercePaymentMethod::ALFA_PAY,
            CommercePaymentMethod::SBP,
            CommercePaymentMethod::SBERPAY,
            CommercePaymentMethod::MIR_PAY,
        ] as $method) {
            self::assertSame(
                CommercePaymentMethodDefinition::PRESENTATION_SECONDARY,
                CommercePaymentMethodCatalogue::get(
                    $method
                )->get_presentation(),
                $method
            );
        }
    }

    public function test_definition_rejects_unknown_presentation(): void {
        $this->expectException(\coding_exception::class);

        new CommercePaymentMethodDefinition(
            'future_pay',
            CommercePaymentMethodDefinition::FAMILY_WALLET,
            true,
            false,
            999,
            'giant_banner'
        );
    }

    public function test_presenter_exposes_unified_action_card_collections(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );

        self::assertStringContainsString(
            'CommercePaymentMethodCatalogue::get(',
            $presenter
        );
        self::assertStringContainsString(
            "'primaryactioncards' => \$primaryactioncards",
            $presenter
        );
        self::assertStringContainsString(
            "'secondaryactioncards' => \$secondaryactioncards",
            $presenter
        );
        self::assertStringContainsString(
            "'hasprimaryactioncards' => \$primaryactioncards !== []",
            $presenter
        );
        self::assertStringContainsString(
            "'hassecondaryactioncards' => \$secondaryactioncards !== []",
            $presenter
        );
    }

    public function test_h1272_migrated_template_to_action_card_rendering(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'page.mustache now consumes action-card buckets',
            $presenter
        );
        self::assertStringContainsString(
            "'quickmethods' => \$quick",
            $presenter
        );
        self::assertStringContainsString(
            '{{#primaryactioncards}}',
            $template
        );
        self::assertStringContainsString(
            '{{#secondaryactioncards}}',
            $template
        );
        self::assertStringNotContainsString(
            '{{#quickmethods}}',
            $template
        );
    }
}
