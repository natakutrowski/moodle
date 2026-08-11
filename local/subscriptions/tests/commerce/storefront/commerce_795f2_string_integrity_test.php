<?php

namespace local_subscriptions;

use advanced_testcase;

final class commerce_795f2_string_integrity_test extends advanced_testcase {
    public function test_all_declared_capabilities_have_language_strings(): void {
        global $CFG;
        $access = file_get_contents($CFG->dirroot . '/local/subscriptions/db/access.php');
        $english = file_get_contents($CFG->dirroot . '/local/subscriptions/lang/en/local_subscriptions.php');
        preg_match_all("/'(local_subscriptions\\/[^']+)'\\s*=>/", $access, $matches);
        foreach ($matches[1] as $capability) {
            $key = str_replace('local_subscriptions/', '', $capability);
            self::assertStringContainsString("\$string['$key']", $english, $capability);
        }
    }

    public function test_storefront_uses_an_existing_subscription_type_string(): void {
        $pluginroot = dirname(__DIR__, 3);
        $catalogue = file_get_contents($pluginroot . '/digital_catalog.php');
        $english = file_get_contents($pluginroot . '/lang/en/local_subscriptions.php');

        $this->assertIsString($catalogue);
        $this->assertIsString($english);
        $this->assertStringContainsString("commerce_purchase_type_subscription", $catalogue);
        $this->assertStringContainsString("\$string['commerce_purchase_type_subscription']", $english);
        $this->assertStringNotContainsString("commerce_type_subscription", $catalogue);
    }
}
