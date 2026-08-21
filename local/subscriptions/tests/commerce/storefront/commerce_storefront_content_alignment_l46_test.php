<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;

final class commerce_storefront_content_alignment_l46_test extends \advanced_testcase {
    public function test_editor_persists_and_reloads_horizontal_content_alignment(): void {
        $editor = new CommerceStorefrontPageEditor();

        $metadata = $editor->merge_submission([], [
            'template' => 'default',
            'section_type_0' => 'cta',
            'section_id_0' => 'final-cta',
            'section_visible_0' => 1,
            'section_title_0' => 'Ready?',
            'section_content_alignment_0' => 'center',
        ], 'fr');

        $section = $metadata['storefront']['sections'][0];
        $this->assertSame('center', $section['contentalignment']);

        $product = new CommerceProduct(
            'TEST.ALIGN',
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ACTIVE,
            'Test',
            '',
            $metadata
        );
        $rows = $editor->form_rows($product, 'fr');

        $this->assertSame('center', $rows[0]['contentalignment']);
    }

    public function test_invalid_alignment_falls_back_to_left(): void {
        $editor = new CommerceStorefrontPageEditor();

        $metadata = $editor->merge_submission([], [
            'template' => 'default',
            'section_type_0' => 'cta',
            'section_id_0' => 'final-cta',
            'section_visible_0' => 1,
            'section_content_alignment_0' => 'diagonal',
        ], 'fr');

        $this->assertSame(
            'left',
            $metadata['storefront']['sections'][0]['contentalignment']
        );
    }

    public function test_public_contract_exposes_content_alignment_class(): void {
        global $CFG;

        $presenter = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'
        );
        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );
        $admin = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );

        $this->assertStringContainsString(
            "'commerce-product-section--content-'",
            $presenter
        );
        $this->assertStringContainsString(
            '.commerce-product-section--content-center > .commerce-product-section__title',
            $css
        );
        $this->assertStringContainsString(
            'section_content_alignment_',
            $admin
        );
    }
}
