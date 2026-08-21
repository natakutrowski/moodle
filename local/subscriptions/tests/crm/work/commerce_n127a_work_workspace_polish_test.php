<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n127a_work_workspace_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_work_pages_share_secondary_navigation(): void {
        foreach ([
            'admin/work/index.php',
            'admin/work/create.php',
            'admin/work/view.php',
            'admin/work/teams.php',
        ] as $file) {
            self::assertStringContainsString(
                'WorkSectionNavigationRenderer::render(',
                $this->file($file)
            );
        }
    }

    public function test_work_renderer_exposes_list_and_detail_workspace_structure(): void {
        $renderer = $this->file(
            'classes/crm/work/rendering/WorkItemRenderer.php'
        );

        foreach ([
            'crm-work-card-reference',
            'crm-work-card-timing',
            'crm-work-detail-summary',
            'crm-work-detail-grid',
            'crm-work-management-panel',
        ] as $class) {
            self::assertStringContainsString(
                $class,
                $renderer
            );
        }
    }

    public function test_create_and_team_pages_have_balanced_layouts(): void {
        self::assertStringContainsString(
            'crm-work-create-grid',
            $this->file(
                'admin/work/create.php'
            )
        );

        self::assertStringContainsString(
            'crm-work-teams-grid',
            $this->file(
                'admin/work/teams.php'
            )
        );
    }
}
