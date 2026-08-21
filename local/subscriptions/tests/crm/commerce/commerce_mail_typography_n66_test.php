<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_mail_typography_n66_test extends advanced_testcase {
    public function test_mail_filter_is_collapsed_by_default_with_sales_style_hint(): void {
        $root = dirname(__DIR__, 3);
        $mail = file_get_contents($root . '/admin/commerce/mail/index.php');

        self::assertStringContainsString(
            'commerce_mail_filters_collapsed_hint',
            $mail
        );
        self::assertStringContainsString(
            "'open' => \$filtersactive ? 'open' : null",
            $mail
        );
    }

    public function test_mail_typography_matches_sales_readability_scale(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents(
            $root . '/styles/commerce_mail_admin.css'
        );

        self::assertStringContainsString(
            '.local-subscriptions-commerce-mail-page .commerce-mail-journal-table',
            $css
        );
        self::assertStringContainsString(
            'font-size: .96rem;',
            $css
        );
        self::assertStringContainsString(
            '.commerce-mail-filter-panel-summary-copy',
            $css
        );
        self::assertStringContainsString(
            'font-size: .86rem;',
            $css
        );
    }

    public function test_no_version_bump_for_css_and_test_polish(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
