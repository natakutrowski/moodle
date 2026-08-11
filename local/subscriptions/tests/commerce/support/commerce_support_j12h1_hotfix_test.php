<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_support_j12h1_hotfix_test extends \advanced_testcase {
    public function test_support_name_and_reference_are_built_safely(): void {
        $root = dirname(__DIR__, 3);
        $controller = file_get_contents($root . '/support_request.php');
        $service = file_get_contents(
            $root . '/classes/commerce/support/CommerceSupportRequestService.php'
        );

        self::assertStringNotContainsString('fullname($customerrecord)', $controller);
        self::assertStringContainsString('trim((string)$customerrecord->firstname)', $controller);
        self::assertStringContainsString("'x-campusfr-support-reference'", $service);
        self::assertStringContainsString(
            "append_line(\$lines, 'commerce_support_reference', \$supportreference)",
            $service
        );
    }

    public function test_currency_selector_keeps_dynamic_currencies_and_filters(): void {
        $root = dirname(__DIR__, 3);
        $controller = file_get_contents($root . '/digital_catalog.php');
        $template = file_get_contents($root . '/templates/storefront/catalog.mustache');
        $currencyselector = file_get_contents(
            $root . '/templates/storefront/currency_selector.mustache'
        );
        $styles = file_get_contents($root . '/styles/storefront.css');

        self::assertStringContainsString('SELECT DISTINCT UPPER(currency)', $controller);
        self::assertStringContainsString('$availablecurrencies', $controller);
        self::assertStringContainsString('name="currency" value="{{currency}}"', $template);

        // Currency chrome is deliberately shared through the partial.
        self::assertStringContainsString(
            '{{> local_subscriptions/storefront/currency_selector }}',
            $template
        );
        self::assertStringContainsString(
            'commerce-storefront__currency-icon',
            $currencyselector
        );
        self::assertStringContainsString('{{#currencies}}', $currencyselector);
        self::assertStringContainsString('commerce-storefront__currency-hint', $styles);
    }
}
