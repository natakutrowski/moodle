<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a54_provisional_account_css_loading_test extends \advanced_testcase {
    public function test_before_footer_no_longer_registers_css(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/output/hook_callbacks.php'
        );

        self::assertStringNotContainsString(
            "->css(new \\moodle_url('/local/subscriptions/styles/provisional_account.css'))",
            $source
        );
        self::assertStringContainsString(
            "'local_subscriptions/provisional_account_notice'",
            $source
        );
    }

    public function test_provisional_account_styles_are_in_plugin_level_stylesheet(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles.css'
        );

        self::assertStringContainsString(
            'H12.9-A5.4 — provisional-account styles loaded with plugin stylesheet.',
            $css
        );
        self::assertStringContainsString(
            '.commerce-provisional-login-notice {',
            $css
        );
        self::assertStringContainsString(
            '.commerce-account-dialog {',
            $css
        );
    }
}
