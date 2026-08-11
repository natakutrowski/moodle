<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_premium_design_l44_test extends \advanced_testcase {
    public function test_builder_exposes_semantic_premium_presentations(): void {
        global $CFG;

        $admin = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront.php'
        );
        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        foreach (['premium', 'statement', 'feature', 'commerce'] as $presentation) {
            $this->assertStringContainsString("'{$presentation}' => get_string(", $admin);
            $this->assertStringContainsString(
                "commerce-product-section--presentation-{$presentation}",
                $css
            );
        }

        $this->assertStringContainsString(
            "'premium', 'statement', 'feature', 'commerce'",
            $editor
        );
        $this->assertStringNotContainsString('DIGITAL_DOWNLOAD.VERBS_PDF', $css);
    }
}
