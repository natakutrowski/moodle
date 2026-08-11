<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_owned_actions_j74_test
        extends \advanced_testcase {
    public function test_owned_course_resolver_targets_public_course_route(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/'
                . 'presentation/CommerceStorefrontUrlResolver.php'
        );

        $this->assertStringContainsString(
            'UrlFactory::course(',
            $source
        );
        $this->assertStringNotContainsString(
            "'/course/view.php'",
            $source
        );
        $this->assertStringContainsString(
            'local_subs_commerce_prod_ent',
            $source
        );
        $this->assertStringContainsString(
            'course_id_from_entitlement',
            $source
        );
    }

    public function test_owned_digital_targets_library_and_exposes_downloads(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/storefront/'
                . 'presentation/CommerceStorefrontUrlResolver.php'
        );
        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/storefront_product.php'
        );
        $panel = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_commerce_panel.mustache'
        );

        $this->assertStringContainsString(
            'UrlFactory::my_digital_products()',
            $resolver
        );
        $this->assertStringNotContainsString(
            'my_digital_products.php',
            $resolver
        );
        $this->assertStringContainsString(
            'CommerceDigitalLibraryService',
            $page
        );
        $this->assertStringContainsString(
            'hasowneddownloads',
            $panel
        );
    }

    public function test_catalogue_uses_typed_placeholder_partial(): void {
        global $CFG;

        $card = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/storefront/'
                . 'product_card.mustache'
        );

        $this->assertStringContainsString(
            'local_subscriptions/storefront/product_placeholder',
            $card
        );
        $this->assertStringNotContainsString(
            '>CampusFR</div>',
            $card
        );
    }
}
