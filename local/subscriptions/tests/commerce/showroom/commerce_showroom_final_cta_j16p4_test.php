<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_final_cta_j16p4_test extends \advanced_testcase {
    public function test_sticky_clearance_and_complete_invoice_profile_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $page = file_get_contents($root . 'showroom.php');
        $css = file_get_contents($root . 'styles/showroom.css');
        $js = file_get_contents($root . 'amd/src/showroom.js');

        foreach (['name', 'address', 'legal', 'email', 'phone', 'website', 'taxnotice', 'footer'] as $field) {
            self::assertStringContainsString(
                "\$legalprofile['{$field}'] ?? ''",
                $page
            );
        }

        self::assertStringContainsString('--showroom-final-mobile-bg-offset-x: 150px;', $css);
        self::assertStringContainsString(
            "sticky.classList.toggle('is-final-cta-active', visible);",
            $js
        );
    }
}
