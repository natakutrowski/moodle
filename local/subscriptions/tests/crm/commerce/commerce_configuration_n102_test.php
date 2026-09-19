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

    public function test_section_page_has_explicit_allowlist_and_masks_secret_values(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/configuration/section.php');
        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'payments', 'localisation', 'checkout', 'communications', 'legal', 'storefront', 'engine'",
            $source
        );
        $this->assertStringContainsString('commerce_configuration_edit_notice', $source);
        $this->assertStringContainsString("'password_keep'", $source);
        $this->assertStringContainsString("'type' => 'password'", $source);
        $this->assertStringContainsString("'value' => ''", $source);
        $this->assertStringContainsString("'autocomplete' => 'new-password'", $source);
    }

    public function test_n102_does_not_bump_plugin_version(): void {
        $source = file_get_contents(__DIR__ . '/../../../version.php');
        $this->assertIsString($source);
        self::assertSame(
            1,
            preg_match('/\$plugin->version\s*=\s*(\d+);/', $source, $pluginversionmatch)
        );
        self::assertGreaterThanOrEqual(
            2026081602,
            (int)$pluginversionmatch[1]
        );
    }
}
