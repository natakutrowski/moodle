<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\navigation\CommerceLegacyCatalogLinkGenerator;

final class commerce_catalog_e11_e12_polish_test extends advanced_testcase {
    public function test_currency_registry_defaults_to_eur_and_rub(): void {
        $this->resetAfterTest();
        unset_config('commerce_enabled_currencies', 'local_subscriptions');
        $this->assertSame(['EUR', 'RUB'], (new CommerceCurrencyRegistry())->enabled());
    }

    public function test_catalog_links_use_central_subscription_config(): void {
        $this->assertStringContainsString(subscription_config::commerce_plan_edit_page(), CommerceLegacyCatalogLinkGenerator::plan_edit_url(3)->out(false));
        $this->assertStringContainsString(subscription_config::commerce_access_scope_edit_page(), CommerceLegacyCatalogLinkGenerator::scope_edit_url(4)->out(false));
        $this->assertStringContainsString(subscription_config::digital_product_edit_admin_page(), CommerceLegacyCatalogLinkGenerator::digital_edit_url(5)->out(false));
    }
}
