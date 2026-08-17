<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_admin_topbar_j12c_test extends \advanced_testcase {
    public function test_admin_navigation_restores_editing_and_account_actions(): void {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/classes/local/customer_navigation.php');
        $template = file_get_contents($root . '/templates/customer_navigation.mustache');

        self::assertStringContainsString('output.edit_switch', $template);
        self::assertStringContainsString('customernavigation.preferencesurl', $template);
        self::assertStringContainsString('customernavigation.canswitchrole', $template);
        self::assertStringContainsString("has_capability('moodle/role:switchroles'", $service);

        $admin = strpos($template, 'customernavigation.adminitems');
        $campus = strpos($template, 'customernavigation.campusurl');
        $cart = strpos($template, 'customernavigation.carturl');
        self::assertLessThan($campus, $admin);
        self::assertLessThan($cart, $campus);
    }

    public function test_crm_registry_icons_are_rendered_as_icons_not_text_symbols(): void {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/classes/local/customer_navigation.php');

        self::assertStringContainsString("\$icon = 'fa ' . \$icon", $service);
        self::assertStringContainsString("\$item->url->out(false),\n                    \$icon", $service);
        self::assertStringNotContainsString("\$item->url->out(false),\n                    '',\n                    (string)\$item->icon", $service);
    }

}
