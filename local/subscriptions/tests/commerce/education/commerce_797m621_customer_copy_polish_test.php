<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797m621_customer_copy_polish_test extends \advanced_testcase {
    public function test_customer_facing_french_reservation_copy_uses_vous(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "commerce_cart_instant_access'] = 'Accès selon les modalités indiquées'",
            $source
        );

        foreach ([
            "commerce_cart_seat_reserved'] = 'Votre place",
            "commerce_cart_seats_reserved_summary'] = 'Vos places",
            "commerce_checkout_seat_reserved'] = 'Votre place",
            "commerce_checkout_seat_reserved_help'] = 'Vous pouvez",
            "commerce_checkout_seat_expired'] = 'Votre réservation",
            "commerce_customer_hub_eyebrow'] = 'Votre espace",
            "commerce_customer_hub_team_my_contribution'] = 'Votre contribution",
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }
}
