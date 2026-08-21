<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n129c_customer_success_plan_creation_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user360_exposes_customer_success_plan_creation(): void {
        $section = $this->file(
            'classes/crm/success/plans/rendering/CustomerSuccessPlanUserSection.php'
        );

        self::assertStringContainsString(
            'admin_customer_success_plan_create_page()',
            $section
        );
        self::assertStringContainsString(
            'csplancreate_button_n129c',
            $section
        );
    }

    public function test_manual_create_page_is_protected_and_integrated(): void {
        $page = $this->file(
            'admin/assistant/plan_create.php'
        );

        self::assertStringContainsString(
            'Capabilities::MANAGE_USERS',
            $page
        );
        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(',
            $page
        );
        self::assertStringContainsString(
            'CustomerSuccessPlanManualCreationService',
            $page
        );
    }

    public function test_creation_service_is_transactional_and_user360_sourced(): void {
        $service = $this->file(
            'classes/crm/success/plans/services/CustomerSuccessPlanManualCreationService.php'
        );

        self::assertStringContainsString(
            'start_delegated_transaction()',
            $service
        );
        self::assertStringContainsString(
            'CustomerSuccessPlanSource::USER_360',
            $service
        );
        self::assertStringContainsString(
            'plan_created(',
            $service
        );
    }
}
