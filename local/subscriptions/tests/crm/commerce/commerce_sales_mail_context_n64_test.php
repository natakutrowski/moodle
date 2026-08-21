<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_sales_mail_context_n64_test extends advanced_testcase {
    public function test_unfinished_checkout_service_contains_product_decorator(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root
            . '/classes/commerce/checkout/guest/'
            . 'CommerceUnfinishedGuestCheckoutCrmService.php'
        );

        self::assertStringContainsString(
            'private function decorate_purchase_products(array $purchases): array',
            $source
        );
        self::assertStringContainsString(
            'CommercePersistenceSchema::TABLE_ITEM',
            $source
        );
    }

    public function test_followup_mail_subject_string_exists_in_all_languages(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                "\$string['commerce_mail_library_subject']",
                $source,
                $language
            );
        }
    }

    public function test_sales_pages_rely_on_breadcrumb_instead_of_local_subnav(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/admin/commerce/purchases/index.php',
            '/admin/commerce/unfinished-checkouts/index.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringNotContainsString(
                'CommerceSalesNavigationRenderer::render',
                $source
            );
        }
    }

    public function test_sales_context_menu_opens_customer_order_details(): void {
        $root = dirname(__DIR__, 3);
        $sales = file_get_contents(
            $root . '/admin/commerce/purchases/index.php'
        );
        $details = file_get_contents(
            $root . '/order_details.php'
        );
        $template = file_get_contents(
            $root . '/templates/order_details/page.mustache'
        );

        self::assertStringContainsString(
            "'/local/subscriptions/order_details.php'",
            $sales
        );
        self::assertStringContainsString(
            "'adminreturn' => 1",
            $sales
        );
        self::assertStringContainsString(
            "optional_param('adminreturn', 0, PARAM_BOOL)",
            $details
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/index.php'",
            $details
        );
        self::assertStringContainsString(
            'Capabilities::MANAGE_SUBSCRIPTIONS',
            $details
        );
        self::assertStringContainsString(
            '{{backlabel}}',
            $template
        );
    }

    public function test_context_menus_close_outside_and_email_actions_are_grouped(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/amd/src/crm_context_menus.js'
        );
        $build = file_get_contents(
            $root . '/amd/build/crm_context_menus.min.js'
        );
        $configurator = file_get_contents(
            $root . '/classes/crm/layout/CrmPageConfigurator.php'
        );
        $mail = file_get_contents(
            $root . '/admin/commerce/mail/index.php'
        );

        self::assertStringContainsString(
            '.crm-sales-row-actions-menu',
            $source
        );
        self::assertStringContainsString(
            '.commerce-mail-row-actions-menu',
            $source
        );
        self::assertStringContainsString(
            "event.target.closest(SELECTOR)",
            $source
        );
        self::assertStringContainsString(
            "event.key === 'Escape'",
            $source
        );
        self::assertNotSame('', trim($build));
        self::assertStringContainsString(
            "'local_subscriptions/crm_context_menus'",
            $configurator
        );

        foreach ([
            "'message' => []",
            "'delivery' => []",
            "'context' => []",
            'commerce_mail_actions_message',
            'commerce_mail_actions_delivery',
            'commerce_mail_actions_context',
            "if (\$items === [])",
        ] as $needle) {
            self::assertStringContainsString($needle, $mail);
        }
    }

    public function test_n64_does_not_require_an_extra_plugin_version_bump(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
