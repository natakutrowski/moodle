<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n102_test extends advanced_testcase {
    public function test_configuration_cards_open_domain_pages(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/index.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("configuration/section.php", $source);
        $this->assertStringContainsString("'key' => 'payments'", $source);
        $this->assertStringContainsString("'key' => 'engine'", $source);
    }

    public function test_section_page_has_explicit_allowlist_and_does_not_render_secrets(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/section.php');
        $this->assertIsString($source);
        $this->assertStringContainsString("'payments', 'localisation', 'checkout', 'communications', 'legal', 'storefront', 'engine'", $source);
        $this->assertStringContainsString('commerce_configuration_section_notice', $source);
        $this->assertStringNotContainsString('stripe_secret', $source);
        $this->assertStringNotContainsString('alfa_password', $source);
        $this->assertStringNotContainsString('openai_api_key', $source);
    }

    public function test_n102_does_not_bump_plugin_version(): void {
        $source = file_get_contents(__DIR__ . '/../../../version.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('$plugin->version = 2026081602;', $source);
    }
}
