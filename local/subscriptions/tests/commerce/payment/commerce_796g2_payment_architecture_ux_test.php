<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g2_payment_architecture_ux_test extends advanced_testcase {
    public function test_provider_names_are_nowrap_and_matrix_note_has_spacing(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );
        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles.css'
        );

        $this->assertStringContainsString(
            'commerce-config-provider-name',
            $page
        );
        $this->assertStringContainsString(
            '.commerce-config-provider-name',
            $styles
        );
        $this->assertStringContainsString(
            'white-space: nowrap',
            $styles
        );
        $this->assertStringContainsString(
            "'alert alert-info mt-3 mb-0'",
            $page
        );
    }
}
