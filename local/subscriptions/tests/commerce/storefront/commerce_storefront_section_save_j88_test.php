<?php

declare(strict_types=1);

namespace local_subscriptions;

/** Contract tests for per-section Storefront persistence. */
final class commerce_storefront_section_save_j88_test extends \advanced_testcase {
    public function test_admin_form_exposes_per_section_save_contract(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_builder.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'enctype' => 'multipart/form-data'",
            $source
        );
        $this->assertStringContainsString(
            "'data-save-section' => '1'",
            $source
        );
        $this->assertStringContainsString(
            'storefront_section_save.php',
            $source
        );
    }

    public function test_ajax_module_submits_real_formdata_with_files(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../amd/src/storefront_builder_drag_drop.js'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('const data = new FormData();', $source);
        $this->assertStringContainsString('Array.from(field.files || [])', $source);
        $this->assertStringContainsString("data.append('section_index', index);", $source);
        $this->assertStringContainsString('window.tinyMCE.get(textarea.id).save();', $source);
    }

    public function test_endpoint_uses_stable_section_save_service(): void {
        $endpoint = file_get_contents(
            __DIR__ . '/../../../admin/commerce/products/storefront_section_save.php'
        );
        $service = file_get_contents(
            __DIR__ . '/../../../classes/commerce/storefront/admin/CommerceStorefrontSectionSaveService.php'
        );

        $this->assertIsString($endpoint);
        $this->assertIsString($service);
        $this->assertStringContainsString('require_sesskey();', $endpoint);
        $this->assertStringContainsString('save_metadata', $endpoint);
        $this->assertStringContainsString('find_section_index', $service);
        $this->assertStringContainsString("'section_content_itemid_'", $service);
        $this->assertStringContainsString('store_uploaded_slot', $service);
    }
    public function test_new_client_side_section_is_upserted_directly(): void {
        $service = file_get_contents(
            __DIR__ . '/../../../classes/commerce/storefront/admin/CommerceStorefrontSectionSaveService.php'
        );

        $this->assertIsString($service);
        $this->assertStringContainsString('single_section_submission', $service);
        $this->assertStringContainsString('$sections[] = $section;', $service);
        $this->assertStringContainsString('$sections[$targetindex] = $section;', $service);
        $this->assertStringNotContainsString('base_submission', $service);
    }

}
