<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

/** J6.7B9.5 Trial banner language and copy contract. */
final class trial_discount_banner_j67b95_test
        extends \advanced_testcase {

    public function test_banner_uses_dedicated_scope_copy(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'trial_discount_banner_prefix',
            $source
        );
        $this->assertStringNotContainsString(
            "get_string('checkout_discount_note_prefix'",
            $source
        );
    }

    public function test_required_strings_exist_in_all_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/campus/lang/'
                . $lang
                . '/local_campus.php'
            );

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "trial_discount_banner_prefix",
                $source
            );
            $this->assertStringContainsString(
                "trial_discount_banner_cta_current_course",
                $source
            );
        }
    }
}
