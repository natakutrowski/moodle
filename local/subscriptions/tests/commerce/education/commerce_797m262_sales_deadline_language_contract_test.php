<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m262_sales_deadline_language_contract_test extends advanced_testcase {
    public function test_sales_deadline_strings_exist_in_all_supported_languages(): void {
        $root = dirname(__DIR__, 3);

        foreach (['en', 'fr', 'ru'] as $lang) {
            $source = file_get_contents(
                $root . '/lang/' . $lang . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                "\$string['commerce_capacity_sales_closed_cta']",
                $source,
                'Missing sales-closed CTA string in ' . $lang
            );
            self::assertStringContainsString(
                "\$string['commerce_capacity_sales_close_at']",
                $source,
                'Missing sales-close deadline string in ' . $lang
            );
        }
    }

    public function test_capacity_presenter_uses_declared_deadline_string(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/education/capacity/CommercePedagogicalCapacityPresenter.php'
        );

        self::assertStringContainsString(
            "'commerce_capacity_sales_close_at'",
            $source
        );
        self::assertStringContainsString(
            "'commerce_capacity_sales_closed_cta'",
            $source
        );
    }
}
