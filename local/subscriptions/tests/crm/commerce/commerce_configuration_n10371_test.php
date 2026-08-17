<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n10371_test extends advanced_testcase {
    public function test_page_context_is_set_before_featured_plan_labels_are_formatted(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');

        $setcontext = strpos($source, '$PAGE->set_context($context);');
        $formatstring = strpos($source, 'format_string((string)$plan->name)');

        $this->assertNotFalse($setcontext);
        $this->assertNotFalse($formatstring);
        $this->assertLessThan($formatstring, $setcontext);
    }

    public function test_previous_tu_do_not_interpolate_php_variables_in_expected_strings(): void {
        $root = dirname(__DIR__, 3);
        foreach ([
            'commerce_configuration_n1035_test.php',
            'commerce_configuration_n1036_test.php',
            'commerce_configuration_n1037_test.php',
        ] as $file) {
            $source = file_get_contents($root . '/tests/crm/commerce/' . $file);
            $this->assertStringNotContainsString('assertStringContainsString("$field(', $source);
        }
    }

    public function test_n10371_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
