<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\admin\CommerceProductLifecycleService;

final class commerce_products_n85_test extends advanced_testcase {
    public function test_product_index_defaults_to_active_products(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/index.php'
        );

        self::assertStringContainsString(
            "optional_param('status', '', PARAM_ALPHANUMEXT)",
            $source
        );
        self::assertStringContainsString(
            '$status = \'active\';',
            $source
        );
        self::assertStringContainsString(
            "'all' => get_string('all')",
            $source
        );
        self::assertStringContainsString(
            "if (\$status !== 'all' && \$row['status'] !== \$status)",
            $source
        );
    }

    public function test_product_editor_uses_resolver_status_badge_and_language_tabs(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/edit.php'
        );

        foreach ([
            'CommerceCatalogProductNameResolver::resolve_native_id',
            'CommerceProductPresentation::status_badge',
            'crm-product-edit-top-grid',
            'crm-product-language-tabs',
            'data-language-tab',
            'commerce_product_fallback_name',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_product_editor_breadcrumb_uses_resolved_product_name(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/products/edit.php'
        );

        self::assertStringContainsString(
            'CommerceProductEditorNavigationRenderer::breadcrumb(',
            $source
        );
        self::assertStringContainsString(
            '$pagetitle,',
            $source
        );
        self::assertStringNotContainsString(
            'breadcrumb($product->get_name()',
            $source
        );
    }

    public function test_identity_change_is_allowed_before_consumption_and_blocked_after_grant(): void {
        global $DB;
        $this->resetAfterTest();
        $now = time();

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
                'sku' => 'N85.OLD',
                'type' => 'digital_download',
                'status' => 'inactive',
                'name' => 'Fallback',
                'description' => '',
                'metadatajson' => '{}',
                'availablefrom' => null,
                'availableuntil' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $lifecycle = new CommerceProductLifecycleService($DB);
        self::assertTrue(
            $lifecycle->can_change_identity($productid, 'N85.OLD')
        );
        $lifecycle->change_identity(
            $productid,
            'N85.OLD',
            'N85.NEW',
            'course_access'
        );

        $record = $DB->get_record(
            'local_subs_commerce_product',
            ['id' => $productid],
            'sku,type',
            MUST_EXIST
        );
        self::assertSame('N85.NEW', $record->sku);
        self::assertSame('course_access', $record->type);

        $DB->insert_record(
            'local_subs_commerce_grant',
            (object)[
                'grantreference' => 'grant-n85',
                'idempotencykey' => 'grant-n85-idem',
                'purchasereference' => 'manual-n85',
                'itemreference' => 'N85.NEW',
                'productsku' => 'N85.NEW',
                'type' => 'course_access',
                'resourcekey' => 'course:1:full',
                'quantity' => 1,
                'beneficiaryuserid' => null,
                'beneficiaryemail' => 'n85@example.test',
                'validfrom' => $now,
                'validuntil' => null,
                'status' => 'active',
                'configurationjson' => '{}',
                'metadatajson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
        self::assertFalse(
            $lifecycle->can_change_identity($productid, 'N85.NEW')
        );
    }

    public function test_product_manager_no_longer_has_absolute_sku_immutability(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/catalog/admin/'
            . 'CommerceCatalogProductManager.php'
        );

        self::assertStringContainsString(
            'CommerceProductLifecycleService($this->db)',
            $source
        );
        self::assertStringNotContainsString(
            'A Commerce product SKU cannot be changed after creation.',
            $source
        );
    }

    public function test_n85_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        preg_match(
            '/\\$plugin->version\\s*=\\s*(\\d+);/',
            $version,
            $matches
        );
        self::assertGreaterThanOrEqual(
            2026081601,
            (int)($matches[1] ?? 0)
        );
    }
}
