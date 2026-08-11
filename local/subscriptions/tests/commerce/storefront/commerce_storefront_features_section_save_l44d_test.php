<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_features_section_save_l44d_test extends \advanced_testcase {
    public function test_individual_section_save_uses_editor_contract_for_features(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontSectionSaveService.php'
        );
        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/storefront_section_save.php'
        );

        $this->assertIsString($service);
        $this->assertStringContainsString(
            "['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features']",
            $service
        );
        $this->assertStringContainsString(
            '$this->files->save_editor(',
            $service
        );

        // AJAX save must return the same three-state editorial status contract
        // as a full Builder page reload.
        $this->assertStringContainsString(
            '$statusservice->status($persistedsection)',
            $endpoint
        );
        $this->assertStringContainsString(
            "'attentionlabel'",
            $endpoint
        );
        $this->assertStringContainsString(
            "'emptylabel'",
            $endpoint
        );
    }
}
