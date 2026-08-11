<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6A customer-facing copy checks. */
final class commerce_restriction_banner_copy_j66a_test
        extends \advanced_testcase {

    public function test_french_copy_distinguishes_single_full_and_multiple_plans(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'unlock_subscriber_button'] = 'Voir les formules'",
            $source
        );
        $this->assertStringContainsString(
            "'unlock_subscriber_button_single'] = 'Acheter le cours'",
            $source
        );
        $this->assertStringContainsString(
            "'unlock_grammar_button'] = 'Acheter la grammaire'",
            $source
        );
        $this->assertStringContainsString(
            "'unlock_full_button'] = 'Acheter la version complète'",
            $source
        );
    }
}
