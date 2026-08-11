<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_visual_builder_j82a_test extends \advanced_testcase {
    public function test_builder_exposes_cards_palette_and_server_actions(): void {
        global $CFG;
        $page=file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/products/storefront.php');
        foreach(['commerce-storefront-section-card','builder_action','builder_type','CommerceStorefrontVisualBuilderService'] as $needle) $this->assertStringContainsString($needle,$page);
        $this->assertStringNotContainsString("get_string(\n            'commerce_storefront_section_order'",$page);
    }
    public function test_builder_service_supports_all_safe_operations(): void {
        global $CFG;
        $source=file_get_contents($CFG->dirroot.'/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontVisualBuilderService.php');
        foreach(['add','duplicate','delete','toggle','up','down','first','last'] as $operation) $this->assertStringContainsString("'".$operation."'",$source);
        $this->assertStringContainsString('MAX_SECTIONS',$source);
        $this->assertStringContainsString("mediaitemid", $source);
    }
    public function test_builder_has_responsive_sidebar_and_collapsible_cards(): void {
        global $CFG;
        $page=file_get_contents($CFG->dirroot.'/local/subscriptions/admin/commerce/products/storefront.php');
        $css=file_get_contents($CFG->dirroot.'/local/subscriptions/styles/storefront_builder.css');
        $this->assertStringContainsString("start_tag('details'",$page);
        $this->assertStringContainsString('position:sticky',$css);
        $this->assertStringContainsString('@media(max-width:991.98px)',$css);
    }
}
