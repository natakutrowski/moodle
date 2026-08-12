<?php

declare(strict_types=1);

namespace local_subscriptions;

/**
 * Contract checks for customer-facing product URLs in Mes Ressources.
 *
 * @coversNothing
 */
final class commerce_digital_public_urls_j10e4_test extends \advanced_testcase {
    public function test_digital_library_uses_canonical_storefront_url_resolver(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/digital/library/'
                . 'CommerceDigitalLibraryService.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'CommerceStorefrontUrlResolver::direct_storefront($product)->out(false)',
            $source
        );
        $this->assertStringContainsString(
            'private function public_product_url(string $sku): ?string',
            $source
        );
        $this->assertStringNotContainsString(
            "new \\moodle_url('/local/subscriptions/storefront_product.php'",
            $source
        );
    }

    public function test_resource_card_uses_presented_product_url_only(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/my_digital_products/'
                . 'components/resource_card.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString('href="{{producturl}}"', $template);
        $this->assertStringNotContainsString('storefront_product.php', $template);
    }
}
