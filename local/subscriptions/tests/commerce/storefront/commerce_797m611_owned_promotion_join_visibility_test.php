<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** M6.1.1 — owned products with an actionable promotion join stay discoverable. */
final class commerce_797m611_owned_promotion_join_visibility_test extends \advanced_testcase {
    public function test_hide_owned_keeps_actionable_promotion_join_cards_visible(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "empty(\$card['owned'])",
            $source
        );
        self::assertStringContainsString(
            "!empty(\$card['promotionjoinpurchasable'])",
            $source
        );
    }

    public function test_owned_filter_copy_explains_the_joinable_exception(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'Masquer les produits déjà acquis sans nouvelle offre',
            $source
        );
        self::assertStringContainsString('reste visible', $source);
        self::assertStringContainsString('promotion', $source);
    }
}
