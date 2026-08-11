<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_edit_toggle_j12d_test extends \advanced_testcase {
    public function test_edit_switch_is_wrapped_as_topbar_control(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/theme/edly/templates/customer_navigation.mustache');
        $css = file_get_contents($CFG->dirroot . '/theme/edly/style/customer-navigation.css');
        self::assertStringContainsString('campus-customer-nav__edit-control', $template);
        self::assertStringContainsString('{{{ output.edit_switch }}}', $template);
        self::assertStringContainsString('align-items: center', $css);
    }
}
