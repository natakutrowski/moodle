<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

final class commerce_796i81_admin_legal_ux_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_configuration_exposes_v1_legal_routing_and_document_versions(): void {
        global $CFG;

        $index = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/index.php'
        );
        $section = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        self::assertStringContainsString(
            'commerce_configuration_fact_legal_documents',
            $index
        );
        self::assertStringContainsString(
            'legal_documents_ru_version',
            $index
        );
        self::assertStringContainsString(
            'legal_documents_row_version',
            $index
        );
        self::assertStringContainsString(
            'commerce_configuration_legal_v1_scope_title',
            $section
        );
        self::assertStringContainsString(
            'commerce_configuration_legal_v1_snapshot_desc',
            $section
        );
    }

    public function test_purchase_detail_exposes_immutable_seller_snapshot(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        self::assertStringContainsString(
            "['legal_entity_snapshot']",
            $source
        );
        self::assertStringContainsString(
            "['legal_entity_key']",
            $source
        );
        self::assertStringContainsString(
            "['market_country']",
            $source
        );
        self::assertStringContainsString(
            "['resolution_rule']",
            $source
        );
        self::assertStringContainsString(
            'commerce_purchase_seller_snapshot_title',
            $source
        );
        self::assertStringContainsString(
            'commerce_purchase_seller_snapshot_unavailable',
            $source
        );
        self::assertStringContainsString(
            'commerce_purchase_seller_snapshot_source_invoice',
            $source
        );
    }

    public function test_i81_requires_no_schema_change(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/version.php'
        );

        self::assertSame(
            1,
            preg_match(
                '/\\$plugin->version\\s*=\\s*(\\d+);/',
                $version,
                $pluginversionmatch
            )
        );
        self::assertGreaterThanOrEqual(
            2026090801,
            (int)$pluginversionmatch[1]
        );
    }
}
