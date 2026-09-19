<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\currency\CurrencyFormatter;

final class commerce_796c101_checkout_and_separator_fix_test extends advanced_testcase {
    public function test_currency_formatter_never_leaks_html_nbsp_entity(): void {
        $formatted = CurrencyFormatter::format_minor_code(590000, 'RUB');

        $this->assertStringNotContainsString('&nbsp;', $formatted);
        $this->assertStringNotContainsString('&#160;', $formatted);
        $this->assertStringContainsString('RUB', $formatted);
        $this->assertStringContainsString('590', str_replace(["\u{00A0}", ' ', ',', '.'], '', $formatted));
    }

    public function test_public_checkout_uses_currency_registry_and_provider_capabilities(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('CommerceCurrencyRegistry', $source);
        $this->assertStringContainsString('supports_currency($currency)', $source);
        $this->assertStringContainsString('$hascompatibleprovider', $source);
        $this->assertStringNotContainsString("in_array(\$currency, ['EUR', 'RUB'], true)", $source);
        $this->assertStringNotContainsString("\$defaultprovider = \$currency === 'RUB' ? 'alfa' : 'stripe'", $source);
    }
}
