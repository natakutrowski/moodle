<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_media_fit_l45_test extends \advanced_testcase {
    public function test_builder_persists_image_fit_and_public_template_supports_contain(): void {
        global $CFG;

        $admin = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront.php');
        $editor = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php');
        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/storefront.css');

        $this->assertStringContainsString('section_image_fit_', $admin);
        $this->assertStringContainsString("['cover', 'contain']", $editor);
        $this->assertStringContainsString("'imagefit' => 'cover'", $editor);
        $this->assertStringContainsString("'imagecontain'", $presenter);
        $this->assertStringContainsString('commerce-image-text--contain', $template);
        $this->assertStringContainsString('.commerce-image-text--contain .commerce-image-text__media img', $css);
    }

    public function test_four_feature_cards_use_balanced_two_column_desktop_grid(): void {
        global $CFG;

        $presenter = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_section.mustache');

        $this->assertStringContainsString("'featuresfour' => count", $presenter);
        $this->assertStringContainsString('commerce-product-features--four', $template);
        $this->assertStringContainsString('col-12 col-md-6', $template);
    }
}
