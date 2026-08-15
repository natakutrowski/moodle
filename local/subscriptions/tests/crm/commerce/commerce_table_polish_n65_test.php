<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_table_polish_n65_test extends advanced_testcase {
    public function test_context_menu_module_floats_menus_outside_table_scrollers(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/amd/src/crm_context_menus.js');

        self::assertStringContainsString(
            "menu.classList.add('is-floating')",
            $source
        );
        self::assertStringContainsString(
            'getBoundingClientRect()',
            $source
        );
        self::assertStringContainsString(
            'window.requestAnimationFrame',
            $source
        );
        self::assertStringContainsString(
            "window.addEventListener('scroll'",
            $source
        );
    }

    public function test_mail_journal_uses_flag_only_language_cell(): void {
        $root = dirname(__DIR__, 3);
        $mail = file_get_contents(
            $root . '/admin/commerce/mail/index.php'
        );
        $presentation = file_get_contents(
            $root
            . '/classes/commerce/mail/admin/'
            . 'CommerceMailAdminPresentation.php'
        );

        self::assertStringContainsString(
            'CommerceMailAdminPresentation::language_flag',
            $mail
        );
        self::assertStringContainsString(
            'commerce-mail-language-flag',
            $mail
        );
        self::assertStringContainsString(
            'public static function language_flag',
            $presentation
        );
    }

    public function test_sales_and_mail_table_polish_contract(): void {
        $root = dirname(__DIR__, 3);
        $salescss = file_get_contents($root . '/styles.css');
        $mailcss = file_get_contents(
            $root . '/styles/commerce_mail_admin.css'
        );

        self::assertStringContainsString(
            '.crm-sales-row-menu.is-floating',
            $salescss
        );
        self::assertStringContainsString(
            '.commerce-mail-row-menu.is-floating',
            $mailcss
        );
        self::assertStringContainsString(
            'border-right: 0 !important',
            $mailcss
        );
        self::assertStringContainsString(
            '.commerce-mail-journal-table tbody tr:hover',
            $mailcss
        );
    }

    public function test_polish_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081510;',
            $version
        );
    }
}
