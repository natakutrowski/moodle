<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_cover_preview_j75a_test
        extends \advanced_testcase {

    public function test_assets_page_exposes_preview_metadata_and_statuses(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/assets.php'
        );

        foreach ([
            'commerce-product-asset-preview--',
            'commerce_product_visual_status_ok',
            'commerce_product_visual_status_fallback',
            'commerce_product_visual_status_missing',
            'getimagesizefromstring',
            'display_size($filesize)',
            'CommerceProductVisualAuditService::placeholder_icon',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_preview_css_preserves_four_target_ratios(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/'
            . 'commerce_product_assets.css'
        );

        foreach (['1 / 1', '4 / 3', '16 / 9', '4 / 5'] as $ratio) {
            $this->assertStringContainsString(
                'aspect-ratio:' . str_replace(' ', '', $ratio),
                str_replace(' ', '', $styles)
            );
        }
        $this->assertStringContainsString(
            'object-fit:cover',
            str_replace(' ', '', $styles)
        );
    }
}
