<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d62_safe_bulk_form_keys_test extends advanced_testcase {
    public function test_bulk_price_form_does_not_use_sku_as_array_key(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
        );

        $this->assertStringContainsString(
            "\$rowkey = 'p' . sha1(\$sku);",
            $contents
        );
        $this->assertStringContainsString(
            "'name' => 'amounts[' . \$rowkey . ']'",
            $contents
        );
        $this->assertStringNotContainsString(
            "'name' => 'amounts[' . \$sku . ']'",
            $contents
        );
        $this->assertStringContainsString(
            "in_array(\$rowkey, \$selected, true)",
            $contents
        );
    }
}
