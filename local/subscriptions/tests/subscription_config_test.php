<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests central plugin routes.
 *
 * @covers \local_subscriptions\subscription_config
 */
final class subscription_config_test extends advanced_testcase {

    public function test_assistant_routes_are_centralized(): void {
        $this->assertSame(
            '/local/subscriptions/admin/assistant/index.php',
            subscription_config::
                admin_crm_assistant_page()
        );

        $this->assertSame(
            '/local/subscriptions/admin/assistant/action.php',
            subscription_config::
                admin_crm_assistant_action_page()
        );

        $this->assertSame(
            '/local/subscriptions/admin/assistant/work_item.php',
            subscription_config::
                admin_crm_assistant_work_item_page()
        );

        $this->assertSame(
            '/local/subscriptions/admin/assistant/plan.php',
            subscription_config::
                admin_customer_success_plan_page()
        );

        $this->assertSame(
            '/local/subscriptions/admin/assistant/plan_action.php',
            subscription_config::
                admin_customer_success_plan_action_page()
        );

        $this->assertSame(
            '/local/subscriptions/admin/assistant/plan_action_confirm.php',
            subscription_config::
                admin_customer_success_plan_confirm_page()
        );
    }

    public function test_plugin_resource_routes_use_plugin_path(): void {
        $this->assertSame(
            '/local/subscriptions/',
            subscription_config::plugin_path()
        );

        $this->assertSame(
            '/local/subscriptions/styles.css',
            subscription_config::
                plugin_stylesheet_page()
        );

        $this->assertSame(
            '/local/subscriptions/ajax/crm_assistant_ask.php',
            subscription_config::
                crm_assistant_ai_endpoint()
        );
    }

    public function test_routes_are_local_plugin_paths(): void {
        $routes = [
            subscription_config::
                admin_crm_assistant_page(),

            subscription_config::
                admin_crm_assistant_action_page(),

            subscription_config::
                admin_crm_assistant_work_item_page(),

            subscription_config::
                admin_customer_success_plan_page(),

            subscription_config::
                admin_customer_success_plan_action_page(),

            subscription_config::
                admin_customer_success_plan_confirm_page(),

            subscription_config::
                plugin_stylesheet_page(),

            subscription_config::
                crm_assistant_ai_endpoint(),
        ];

        foreach ($routes as $route) {
            $this->assertStringStartsWith(
                subscription_config::plugin_path(),
                $route
            );

            $this->assertStringNotContainsString(
                '..',
                $route
            );
        }
    }
}