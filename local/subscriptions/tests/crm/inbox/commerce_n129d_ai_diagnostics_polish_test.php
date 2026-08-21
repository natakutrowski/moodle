<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n129d_ai_diagnostics_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_ai_diagnostics_uses_structured_dashboard_layout(): void {
        $page = $this->file('admin/inbox/ai_diagnostics.php');

        self::assertStringContainsString(
            'crm-ai-diagnostics-summary',
            $page
        );
        self::assertStringContainsString(
            'crm-ai-diagnostics-checks',
            $page
        );
        self::assertStringContainsString(
            'crm-ai-diagnostics-meter',
            $page
        );
        self::assertStringNotContainsString(
            "'alert alert-success'",
            $page
        );
    }

    public function test_cancelled_plan_exposes_new_plan_cta(): void {
        $renderer = $this->file(
            'classes/crm/success/plans/rendering/CustomerSuccessPlanRenderer.php'
        );

        self::assertStringContainsString(
            'CustomerSuccessPlanStatus::CANCELLED',
            $renderer
        );
        self::assertStringContainsString(
            'admin_customer_success_plan_create_page()',
            $renderer
        );
        self::assertStringContainsString(
            'csplancreate_new_after_cancel_n129d',
            $renderer
        );
    }
}
