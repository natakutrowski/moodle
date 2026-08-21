<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n129b_assistant_plan_subpages_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_plan_page_uses_crm_shell_without_redundant_back_link(): void {
        $page = $this->file(
            'admin/assistant/plan.php'
        );

        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(',
            $page
        );
        self::assertStringContainsString(
            'CrmBreadcrumbRenderer::render(',
            $page
        );
        self::assertStringNotContainsString(
            'CrmBackLinkRenderer::render(',
            $page
        );
    }

    public function test_plan_confirmation_is_integrated_in_crm_workspace(): void {
        $page = $this->file(
            'admin/assistant/plan_action_confirm.php'
        );

        self::assertStringContainsString(
            'CrmPageConfigurator::configure(',
            $page
        );
        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(',
            $page
        );
        self::assertStringContainsString(
            'crm-cs-plan-confirm',
            $page
        );
        self::assertStringNotContainsString(
            '$OUTPUT->confirm(',
            $page
        );
    }

    public function test_customer_success_renderer_has_structured_plan_cards(): void {
        $renderer = $this->file(
            'classes/crm/success/plans/rendering/CustomerSuccessPlanRenderer.php'
        );

        foreach ([
            'local-subscriptions-cs-plan__eyebrow',
            'local-subscriptions-cs-plan__progress-summary',
            'local-subscriptions-cs-plan__step-header',
            'local-subscriptions-cs-plan__actions-panel',
        ] as $class) {
            self::assertStringContainsString(
                $class,
                $renderer
            );
        }
    }
}
