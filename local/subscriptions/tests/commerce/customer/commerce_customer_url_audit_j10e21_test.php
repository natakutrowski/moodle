<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_customer_url_audit_j10e21_test extends \advanced_testcase {
    public function test_audit_classifies_resolvers_and_page_controllers_as_internal_targets(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cli/commerce/audit_public_urls.php'
        );
        $this->assertIsString($source);
        $this->assertStringContainsString('/my_purchases.php', $source);
        $this->assertStringContainsString('/storefront_product.php', $source);
        $this->assertStringContainsString('CommerceStorefrontUrlResolver.php', $source);
    }
}
