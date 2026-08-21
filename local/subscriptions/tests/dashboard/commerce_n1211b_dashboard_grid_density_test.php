<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1211b_dashboard_grid_density_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_team_card_uses_dedicated_utility_zone(): void {
        $factory = $this->file(
            'classes/dashboard/workspace/DashboardWorkspaceFactory.php'
        );
        $registry = $this->file(
            'classes/dashboard/personalization/DashboardCardRegistry.php'
        );
        $dashboard = $this->file(
            'classes/dashboard/Dashboard.php'
        );

        self::assertStringContainsString(
            "ZONE_UTILITY = 'utility'",
            $factory
        );
        self::assertStringContainsString(
            'zone: self::ZONE_UTILITY',
            $registry
        );
        self::assertStringContainsString(
            'DashboardWorkspaceFactory::ZONE_UTILITY',
            $dashboard
        );
    }

    public function test_dashboard_long_lists_are_capped(): void {
        self::assertStringContainsString(
            'array_slice($priorities, 0, 4)',
            $this->file(
                'classes/dashboard/cards/CrmDailyPrioritiesCard.php'
            )
        );

        self::assertStringContainsString(
            'array_slice($alerts, 0, 3)',
            $this->file(
                'classes/dashboard/cards/CrmIntelligenceAlertsCard.php'
            )
        );

        self::assertStringContainsString(
            '$overview->priorityProfiles,',
            $this->file(
                'classes/dashboard/cards/CrmIntelligenceCard.php'
            )
        );
    }

    public function test_compact_onboarding_only_renders_next_steps(): void {
        $renderer = $this->file(
            'classes/crm/help/onboarding/HelpOnboardingRenderer.php'
        );

        self::assertStringContainsString(
            'array_slice(',
            $renderer
        );
        self::assertStringContainsString(
            'dashboard_onboarding_open_full_n1211b',
            $renderer
        );
    }
}
