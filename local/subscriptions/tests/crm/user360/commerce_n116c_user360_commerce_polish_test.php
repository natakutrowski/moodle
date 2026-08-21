<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116c_user360_commerce_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_course_progress_uses_human_access_states_and_expired_row(): void {
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
        self::assertStringContainsString(
            "'crm-user360-n114b-course-row'",
            $renderer
        );
        self::assertStringContainsString(
            "' is-expired'",
            $renderer
        );
        self::assertStringNotContainsString(
            "crm_user360_n114b_access_active",
            $renderer
        );
    }

    public function test_course_latest_activity_is_rendered_inside_progress_block(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            '. $latestactivity,',
            $renderer
        );
        self::assertStringContainsString(
            "'crm-user360-n114b-progress-copy'",
            $renderer
        );
    }

    public function test_advanced_orders_follow_sales_column_order_and_link_to_sales_view(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'CommercePurchasePresentation::type_badge(',
            $renderer
        );
        self::assertStringContainsString(
            'commercial_status_badge(',
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/view.php'",
            $renderer
        );
        self::assertStringContainsString(
            'commerce_sales_action_resend_access',
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_access.php'",
            $renderer
        );
    }

    public function test_legacy_subscription_period_is_single_line_and_extend_is_removed(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            "'crm-user360-n116a-period'",
            $renderer
        );
        self::assertStringNotContainsString(
            "'action' => 'extend'",
            $renderer
        );
        self::assertStringNotContainsString(
            "'days' => 30",
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
