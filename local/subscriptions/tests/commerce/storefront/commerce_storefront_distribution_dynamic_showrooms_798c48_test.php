<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_distribution_dynamic_showrooms_798c48_test extends \advanced_testcase {
    public function test_distribution_selector_uses_published_cms_showrooms(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_distribution.php'
        );

        self::assertStringContainsString('CommerceShowroomCmsRepository', $source);
        self::assertStringContainsString('CommerceShowroomPublishedDefinitionResolver', $source);
        self::assertStringContainsString("(string)\$candidate->status !== 'published'", $source);
        self::assertStringNotContainsString('CommerceShowroomRegistry::definitions()', $source);
    }

    public function test_discovery_and_product_link_services_are_not_registry_limited(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/catalog/presentation/CommerceProductDiscoveryUrlResolver.php'
        );
        $linkservice = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductLinkService.php'
        );

        self::assertStringContainsString('CommerceShowroomPublishedDefinitionResolver', $resolver);
        self::assertStringNotContainsString('CommerceShowroomRegistry::get($showroomkey)', $resolver);
        self::assertStringContainsString('CommerceShowroomPublishedDefinitionResolver', $linkservice);
        self::assertStringNotContainsString('CommerceShowroomRegistry::get($key)', $linkservice);
    }
}
