<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class customer_hook_j10e21_test extends \advanced_testcase {
    public function test_output_hook_uses_supported_moodle_five_callback(): void {
        global $CFG;

        $hooks = file_get_contents(
            $CFG->dirroot . '/local/campus/db/hooks.php'
        );
        $callbacks = file_get_contents(
            $CFG->dirroot
                . '/local/campus/classes/hooks/output/callbacks.php'
        );
        $lib = file_get_contents(
            $CFG->dirroot . '/local/campus/lib.php'
        );

        $this->assertIsString($hooks);
        $this->assertIsString($callbacks);
        $this->assertIsString($lib);
        $this->assertStringContainsString(
            'before_http_headers::class',
            $hooks
        );
        $this->assertStringContainsString(
            "'before_http_headers'",
            $hooks
        );
        $this->assertStringContainsString(
            'public static function before_http_headers',
            $callbacks
        );
        $this->assertStringNotContainsString(
            'function local_campus_before_standard_html_head',
            $lib
        );
    }
}
