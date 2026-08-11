<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class customer_navigation_storefront_j10e71_test extends \advanced_testcase {
    public function test_storefront_does_not_emit_end_of_body_twice(): void {
        global $CFG;

        $shell = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/storefront_shell.mustache'
        );
        $footer = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/theme_boost/footer.mustache'
        );

        $this->assertIsString($shell);
        $this->assertIsString($footer);
        $this->assertSame(1, substr_count($shell, 'output.standard_end_of_body_html'));
        $this->assertStringContainsString('{{#storefrontminimal}}', $shell);
        $this->assertSame(1, substr_count($footer, 'output.standard_end_of_body_html'));
    }

    public function test_mobile_menu_uses_page_requirements_api(): void {
        global $CFG;

        $navbar = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/theme_boost/navbar.mustache'
        );
        $context = file_get_contents(
            $CFG->dirroot . '/theme/edly/inc/edly_themehandler_context.php'
        );

        $this->assertIsString($navbar);
        $this->assertIsString($context);
        $this->assertStringNotContainsString(
            "require(['theme_edly/mobile_menu']",
            $navbar
        );
        $this->assertStringContainsString(
            '$PAGE->requires->js_call_amd(\'theme_edly/mobile_menu\', \'init\');',
            $context
        );
    }
}
