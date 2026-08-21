<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1211c_dashboard_operations_team_activity_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 2) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_operations_use_dedicated_side_zone(): void {
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
            "ZONE_OPERATIONS_SIDE = 'operations_side'",
            $factory
        );

        self::assertSame(
            2,
            substr_count(
                $registry,
                'zone: self::ZONE_OPERATIONS_SIDE'
            )
        );

        self::assertStringContainsString(
            'render_operations_section',
            $dashboard
        );

        self::assertStringContainsString(
            'DashboardWorkspaceFactory::ZONE_OPERATIONS_SIDE',
            $dashboard
        );
    }

    public function test_team_card_is_native_collapsible_and_closed_by_default(): void {
        $team = $this->file(
            'classes/dashboard/cards/TeamCard.php'
        );

        self::assertStringContainsString(
            "'details'",
            $team
        );

        self::assertStringContainsString(
            "'summary'",
            $team
        );

        self::assertStringNotContainsString(
            "'open' =>",
            $team
        );

        self::assertStringContainsString(
            'dashboard_permission_users',
            $team
        );

        self::assertStringContainsString(
            'dashboard_permission_configuration',
            $team
        );
    }

    public function test_intelligence_badges_share_one_visual_component(): void {
        $card = $this->file(
            'classes/dashboard/cards/CrmIntelligenceCard.php'
        );

        self::assertStringContainsString(
            'crm-intelligence-inbox-badge-conversations',
            $card
        );

        self::assertStringContainsString(
            'crm-intelligence-inbox-badge-open',
            $card
        );

        self::assertStringContainsString(
            'crm-intelligence-inbox-badge-unread',
            $card
        );
    }

    public function test_recent_activity_uses_explicit_aligned_row(): void {
        $activity = $this->file(
            'classes/dashboard/cards/ActivityCard.php'
        );

        self::assertStringContainsString(
            'dashboard-activity-row',
            $activity
        );

        self::assertStringContainsString(
            'dashboard-activity-target',
            $activity
        );

        self::assertStringContainsString(
            'dashboard-activity-actor',
            $activity
        );

        self::assertStringContainsString(
            'dashboard-activity-time',
            $activity
        );
    }
}
