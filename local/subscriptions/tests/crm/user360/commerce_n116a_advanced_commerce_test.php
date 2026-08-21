<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116a_advanced_commerce_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_advanced_commerce_has_only_orders_and_legacy_subscriptions(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'self::orders($profile->commercepurchases ?? [])',
            $renderer
        );
        self::assertStringContainsString(
            'self::legacy_subscriptions($profile->subscriptions ?? [])',
            $renderer
        );
        self::assertStringNotContainsString(
            'render_courses_content',
            $renderer
        );
        self::assertStringNotContainsString(
            'render_digital_purchases_content',
            $renderer
        );
    }

    public function test_orders_use_contextual_menu_and_internal_navigation(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'crm-sales-row-actions-menu',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-row-menu',
            $renderer
        );
        self::assertStringContainsString(
            "'/course/view.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/products/view.php'",
            $renderer
        );
    }

    public function test_legacy_subscription_actions_are_grouped_in_context_menu(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        foreach ([
            "'welcome'",
            "'access'",
            "'receipt'",
        ] as $expected) {
            self::assertStringContainsString(
                $expected,
                $renderer
            );
        }

        self::assertStringNotContainsString(
            "'action' => 'extend'",
            $renderer
        );
    }

    public function test_support_course_progress_exposes_human_access_validity(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'crm_user360_n116c_access_lifetime',
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n116c_access_until',
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n116c_access_expired',
            $renderer
        );
        self::assertStringNotContainsString(
            'crm_user360_n116a_access_period',
            $renderer
        );
        self::assertStringNotContainsString(
            'crm_user360_n114b_access_active',
            $renderer
        );
    }

    public function test_legacy_subscription_menu_uses_sectioned_context_menu(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            "return self::context_menu(\$sections);",
            $renderer
        );
        self::assertStringNotContainsString(
            'menu_separator(',
            $renderer
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
