<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h81_payment_brand_sizing_copy_width_test extends advanced_testcase {
    public function test_non_card_provider_icons_and_wordmarks_use_25px_height(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            'height: 25px;',
            $css
        );
        $this->assertStringContainsString(
            'max-height: 25px;',
            $css
        );
    }

    public function test_non_card_cards_use_wider_copy_and_narrower_logo_column(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        foreach ([
            '1.15rem',
            '2.6rem',
            'minmax(0, 1fr)',
            '4.85rem',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $css
            );
        }
    }

    public function test_french_link_and_klarna_copy_is_shorter(): void {
        global $CFG;

        $lang = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        $this->assertStringContainsString(
            'Paiement rapide avec Link.',
            $lang
        );
        $this->assertStringContainsString(
            'Paiement flexible avec Klarna.',
            $lang
        );
    }
}
