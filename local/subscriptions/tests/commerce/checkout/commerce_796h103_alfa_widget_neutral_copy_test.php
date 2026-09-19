<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h103_alfa_widget_neutral_copy_test extends advanced_testcase {
    public function test_french_alfa_widget_copy_is_customer_neutral(): void {
        global $CFG;

        $lang = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        foreach ([
            "Paiement sécurisé",
            "Le formulaire sécurisé de paiement s’ouvre directement depuis CampusFR.",
            "Continuer le paiement",
            "Le formulaire de paiement n’a pas pu être préparé. Réessayez.",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $lang
            );
        }

        $this->assertStringNotContainsString(
            "Ouvrir le paiement AlfaBank",
            $lang
        );
    }

    public function test_live_widget_endpoint_is_locked_to_working_production_pair(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaWidgetConfiguration.php'
        );

        $this->assertStringContainsString(
            'https://acspayzonaecom.com/assets/alfa-payment.js',
            $source
        );
        $this->assertStringContainsString(
            "? 'payment'",
            $source
        );
        $this->assertStringNotContainsString(
            'https://pay2.alfabank.ru/assets/alfa-payment.js',
            $source
        );
    }
}
