<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;

final class commerce_catalog_e16_e17_media_workflow_test extends advanced_testcase {
    public function test_media_uses_moodle_file_api_area(): void {
        $this->assertSame('local_subscriptions', CommerceCatalogMediaManager::COMPONENT);
        $this->assertSame('catalog_media', CommerceCatalogMediaManager::FILEAREA);
        $this->assertSame('cover', CommerceCatalogMediaManager::ROLE_COVER);
    }

    public function test_activation_endpoint_requires_sesskey_and_validator(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/status.php');
        $this->assertStringContainsString('require_sesskey()', $source);
        $this->assertStringContainsString('CommerceCatalogActivationValidator', $source);
        $this->assertStringContainsString('CommerceProductStatus::ACTIVE', $source);
    }

    public function test_manual_mkdir_is_not_used_for_catalogue_media(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');
        $this->assertStringContainsString('create_file_from_pathname', $source);
        $this->assertStringNotContainsString('mkdir(', $source);
        $this->assertStringNotContainsString('move_uploaded_file(', $source);
    }
}
