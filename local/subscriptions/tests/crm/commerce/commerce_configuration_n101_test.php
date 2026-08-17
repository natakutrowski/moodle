<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_configuration_n101_test extends \advanced_testcase {
    public function test_configuration_hub_separates_catalogue_objects_from_system_settings(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/index.php');
        $this->assertStringContainsString('commerce_configuration_system_title', $source);
        $this->assertStringNotContainsString('commerce_configuration_catalogue_title', $source);
        $this->assertStringNotContainsString('commerce_access_scopes_page()', $source);
        $this->assertStringNotContainsString('commerce_plans_page()', $source);
        $this->assertStringNotContainsString('commerce_configuration_promotions_title', $source);
    }

    public function test_configuration_hub_surfaces_major_system_domains_without_secrets(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/index.php');
        foreach (['payments', 'localisation', 'checkout', 'communications', 'legal', 'storefront', 'engine'] as $domain) {
            $this->assertStringContainsString('commerce_configuration_' . $domain . '_title', $source);
        }
        $this->assertStringNotContainsString("stripe_live_secret", $source);
        $this->assertStringNotContainsString("alfa_live_password", $source);
        $this->assertStringContainsString("section' => 'local_subscriptions_settings'", $source);
    }

    public function test_n101_language_contract_exists_in_all_supported_languages(): void {
        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(__DIR__ . '/../../../lang/' . $lang . '/local_subscriptions.php');
            $this->assertStringContainsString("commerce_configuration_description_n101", $source);
            $this->assertStringContainsString("commerce_configuration_catalogue_step_scope", $source);
            $this->assertStringContainsString("commerce_configuration_engine_title", $source);
        }
    }
}
