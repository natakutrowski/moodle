<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795f2_storefront_catalog_test
        extends \advanced_testcase {

    public function test_catalogue_uses_storefront_boundary_and_templates(): void {
        global $CFG;

        $catalog = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/digital_catalog.php'
        );

        $this->assertIsString($catalog);
        $this->assertStringContainsString(
            'CommerceStorefrontRepository',
            $catalog
        );
        $this->assertStringContainsString(
            "local_subscriptions/storefront/catalog",
            $catalog
        );
        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_card.mustache'
        );
        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/product_price.mustache'
        );
    }

    public function test_storefront_assets_and_cover_projection_exist(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/storefront/'
            . 'presentation/CommerceStorefrontPresenter.php'
        );

        $this->assertStringContainsString('coverurl', $presenter);
        $this->assertFileExists(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );
        $this->assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_badges.mustache'
        );
    }
}
