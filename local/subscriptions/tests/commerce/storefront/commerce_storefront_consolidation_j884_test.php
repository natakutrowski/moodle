<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionSaveService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

final class commerce_storefront_consolidation_j884_test extends \advanced_testcase {
    public function test_new_video_section_keeps_stable_id_and_media_item_id(): void {
        $this->resetAfterTest();
        $product = new CommerceProduct(
            'J884-VIDEO',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'Video test',
            '',
            ['storefront' => ['locales' => ['fr' => ['sections' => []]]]]
        );
        $service = new CommerceStorefrontSectionSaveService(
            new CommerceStorefrontPageEditor(),
            CommerceStorefrontContentFileService::create()
        );
        $result = $service->save($product, 'fr', [
            'section_index' => 0,
            'section_id_0' => '',
            'section_type_0' => 'video',
            'section_visible_0' => 1,
            'section_title_0' => 'Vidéo',
            'section_video_source_0' => 'upload',
            'section_video_ratio_0' => '16_9',
            'section_content_itemid_0' => 0,
        ]);

        $section = $result['section'];
        $this->assertStringStartsWith('section-', (string)$section['id']);
        $this->assertGreaterThan(0, (int)$section['mediaitemid']);
        $this->assertSame(
            (int)$section['mediaitemid'],
            (int)$result['metadata']['storefront']['locales']['fr']['sections'][0]['mediaitemid']
        );
    }

    public function test_admin_restores_transfer_global_zones_and_native_dialog(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php');
        $this->assertStringContainsString('CommerceStorefrontPackageService', $source);
        $this->assertStringContainsString('storefront_global_zones', $source);
        $this->assertStringContainsString("start_tag('dialog'", $source);
        $this->assertStringNotContainsString("start_div('modal fade'", $source);
        $this->assertSame(1, substr_count($source, 'commerce_storefront_reset_confirm_button'));
    }

    public function test_public_rich_content_keeps_media_html_after_pluginfile_rewrite(): void {
        global $CFG;
        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache'
        );
        $this->assertStringContainsString("'noclean' => true", $presenter);
        $this->assertStringContainsString('rewrite_for_display', $presenter);
        $this->assertStringContainsString('{{{videohtml}}}', $template);
        $this->assertStringContainsString("'videohtml' => \$videohtml", $presenter);
    }


    public function test_editor_area_limit_allows_multiple_rich_media_files(): void {
        $this->assertGreaterThan(
            CommerceStorefrontContentFileService::MAX_BYTES,
            CommerceStorefrontContentFileService::AREA_MAX_BYTES
        );
    }

    public function test_read_only_media_audit_cli_targets_any_sku(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cli/commerce/audit_storefront_media.php'
        );
        $this->assertStringContainsString("'sku' => 'SUB.PLAN.30'", $source);
        $this->assertStringContainsString("'json' => false", $source);
        $this->assertStringNotContainsString('--repair', $source);
    }

    public function test_media_upload_uses_stable_aliases_and_video_renders_rich_content(): void {
        $root = dirname(__DIR__, 3);
        $javascript = file_get_contents(
            $root . '/amd/src/storefront_builder_drag_drop.js'
        );
        $service = file_get_contents(
            $root . '/classes/commerce/storefront/admin/CommerceStorefrontSectionSaveService.php'
        );
        $presenter = file_get_contents(
            $root . '/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );

        $this->assertIsString($javascript);
        $this->assertIsString($service);
        $this->assertIsString($presenter);
        $this->assertStringContainsString('storefront_media_video', $javascript);
        $this->assertStringContainsString('storefront_media_video', $service);
        $this->assertStringContainsString('assert_uploaded_slot', $service);
        $this->assertStringContainsString(
            "(string)(\$section['content'] ?? (\$section['caption'] ?? ''))",
            $presenter
        );
    }

    public function test_image_text_uses_plugin_language_strings_for_alignment(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );

        $this->assertStringContainsString(
            "'commerce_storefront_image_position_left'",
            $source
        );
        $this->assertStringContainsString(
            "'commerce_storefront_image_position_right'",
            $source
        );
        $this->assertStringNotContainsString("get_string('left')", $source);
        $this->assertStringNotContainsString("get_string('right')", $source);
    }

    public function test_storefront_admin_header_is_neutralised_for_edly(): void {
        global $CFG;
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront_builder.css'
        );

        $this->assertStringContainsString(
            '.crm-commerce-page-header {',
            $css
        );
        $this->assertStringContainsString(
            'background: transparent !important;',
            $css
        );
    }

}
