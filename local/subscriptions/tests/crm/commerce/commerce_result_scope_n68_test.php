<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_result_scope_n68_test extends advanced_testcase {
    public function test_sales_result_header_exposes_period_and_removable_filter_pills(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );

        foreach ([
            '$salesscopepills = [];',
            'commerce_result_scope_period_range',
            'commerce_result_scope_search',
            'commerce_result_scope_provider',
            'commerce_result_scope_payment',
            'commerce_result_scope_remove_filter_named',
            'crm-result-scope-pills',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            "\$period !== '30'",
            $source
        );
        self::assertStringContainsString(
            "\$salesremoveurl(\$salesscopeparams, 'provider')",
            $source
        );
    }

    public function test_mail_result_header_exposes_scope_and_audit_visibility(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/mail/index.php'
        );

        foreach ([
            '$mailscopepills = [];',
            'commerce_result_scope_period_range',
            'commerce_result_scope_mail_type',
            'commerce_result_scope_mail_status',
            'commerce_result_scope_language',
            'commerce_result_scope_audit_included',
            'commerce_result_scope_audit_excluded',
            'crm-result-scope-pills',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }

        self::assertStringContainsString(
            "\$mailremoveurl(\$mailscopeparams, 'includeaudit')",
            $source
        );
    }

    public function test_scope_pills_are_styled_as_compact_removable_context(): void {
        $root = dirname(__DIR__, 3);
        $css = file_get_contents($root . '/styles.css');

        foreach ([
            '.crm-result-summary',
            '.crm-result-scope-pills',
            '.crm-result-scope-pill',
            '.crm-result-scope-pill-remove',
            '.crm-result-scope-pill.is-audit-included',
        ] as $needle) {
            self::assertStringContainsString($needle, $css);
        }
    }

    public function test_n68_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081510;',
            $version
        );
    }
}
