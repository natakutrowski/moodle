<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d41_currency_label_namespace_test extends advanced_testcase {
    public function test_product_prices_page_uses_actual_currency_label_formatter_namespace(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/prices.php'
        );

        $this->assertStringContainsString(
            'use local_subscriptions\\currency\\CommerceCurrencyLabelFormatter;',
            $contents
        );
        $this->assertStringNotContainsString(
            'use local_subscriptions\\commerce\\currency\\CommerceCurrencyLabelFormatter;',
            $contents
        );
    }
}
