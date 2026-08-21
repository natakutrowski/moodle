<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1211a_dashboard_information_architecture_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_dashboard_uses_semantic_workspace_zones(): void {
        $factory = $this->file(
            'classes/dashboard/workspace/DashboardWorkspaceFactory.php'
        );

        foreach ([
            'ZONE_FOCUS',
            'ZONE_OPERATIONS',
            'ZONE_INSIGHTS',
            'ZONE_SECONDARY',
        ] as $zone) {
            self::assertStringContainsString(
                $zone,
                $factory
            );
        }

        $dashboard = $this->file(
            'classes/dashboard/Dashboard.php'
        );

        self::assertStringContainsString(
            'render_dashboard_section(',
            $dashboard
        );
        self::assertStringNotContainsString(
            'local-subscriptions-dashboard-side',
            $dashboard
        );
    }

    public function test_dashboard_keeps_personalization_but_reduces_default_noise(): void {
        $registry = $this->file(
            'classes/dashboard/personalization/DashboardCardRegistry.php'
        );

        self::assertStringContainsString(
            "'navigation' => self::definition(",
            $registry
        );
        self::assertStringContainsString(
            'defaultvisible: false',
            $registry
        );

        $dashboard = $this->file(
            'classes/dashboard/Dashboard.php'
        );
        self::assertStringContainsString(
            'DashboardPersonalizationRenderer::render(',
            $dashboard
        );
    }

    public function test_long_dashboard_lists_are_capped(): void {
        $priorities = $this->file(
            'classes/dashboard/cards/CrmDailyPrioritiesCard.php'
        );
        self::assertStringContainsString(
            'array_slice($priorities, 0, 4)',
            $priorities
        );

        $alerts = $this->file(
            'classes/dashboard/cards/CrmIntelligenceAlertsCard.php'
        );
        self::assertStringContainsString(
            'array_slice($alerts, 0, 3)',
            $alerts
        );
    }
}
