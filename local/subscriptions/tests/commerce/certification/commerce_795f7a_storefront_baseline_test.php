<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\certification;

use local_subscriptions\commerce\certification\CommerceStorefrontBaselineAuditor;
use local_subscriptions\commerce\certification\CommerceStorefrontBaselineReport;

defined('MOODLE_INTERNAL') || die();

final class commerce_795f7a_storefront_baseline_test extends \advanced_testcase {
    public function test_report_classifies_issues_and_blocks_certification(): void {
        $report = new CommerceStorefrontBaselineReport();
        $report->add_check('schema', true);
        $report->add_inventory('products', 4);
        $report->add_issue('important', 'missing_translation', 'Translation missing.');

        self::assertFalse($report->has_blocking_issues());
        self::assertTrue($report->is_certifiable_baseline());
        self::assertSame(1, $report->count_issues('important'));

        $report->add_issue('blocking', 'duplicate_sku', 'Duplicate SKU.');

        self::assertTrue($report->has_blocking_issues());
        self::assertFalse($report->is_certifiable_baseline());
        self::assertSame(2, $report->count_issues());
    }

    public function test_auditor_is_read_only_and_returns_structured_inventory(): void {
        global $DB;

        $this->resetAfterTest(true);

        $before = $DB->count_records('local_subs_commerce_product');
        $report = (new CommerceStorefrontBaselineAuditor($DB))->audit();
        $after = $DB->count_records('local_subs_commerce_product');

        self::assertSame($before, $after);
        self::assertArrayHasKey('native_products_total', $report->get_inventory());
        self::assertArrayHasKey('table_products', $report->get_checks());
    }

    public function test_cli_has_no_implicit_write_mode(): void {
        $path = dirname(__DIR__, 3) . '/cli/commerce/audit_795f7a_storefront_baseline.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringNotContainsString('insert_record(', $source);
        self::assertStringNotContainsString('update_record(', $source);
        self::assertStringNotContainsString('delete_records(', $source);
        self::assertStringContainsString("'output' => null", $source);
    }
}
