<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n128a_admin_tools_workspace_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_admin_tool_pages_share_secondary_navigation(): void {
        foreach ([
            'admin/tools/index.php',
            'admin/tools/action.php',
            'admin/tools/history.php',
        ] as $file) {
            self::assertStringContainsString(
                'AdminToolsSectionNavigationRenderer::render(',
                $this->file($file)
            );
        }
    }

    public function test_catalogue_has_human_groups(): void {
        $renderer = $this->file(
            'classes/crm/admin_tools/rendering/AdminToolRenderer.php'
        );

        foreach ([
            'crm_admin_tools_group_messaging_n128a',
            'crm_admin_tools_group_automation_n128a',
            'crm_admin_tools_group_operations_n128a',
        ] as $stringkey) {
            self::assertStringContainsString(
                $stringkey,
                $renderer
            );
        }
    }

    public function test_action_and_history_use_new_structures(): void {
        self::assertStringContainsString(
            'crm-admin-tool-execution-grid',
            $this->file('admin/tools/action.php')
        );

        $renderer = $this->file(
            'classes/crm/admin_tools/rendering/AdminToolRenderer.php'
        );

        self::assertStringContainsString(
            'crm-admin-tool-history-status',
            $renderer
        );
        self::assertStringContainsString(
            'crm-admin-tool-history-tool-cell',
            $renderer
        );
    }

    public function test_index_has_breadcrumb_and_navigation_follows_header(): void {
        $index = $this->file('admin/tools/index.php');

        self::assertStringContainsString(
            'CrmBreadcrumbRenderer::render(',
            $index
        );

        $headerposition = strpos(
            $index,
            'CrmPageHeader::render('
        );
        $navposition = strpos(
            $index,
            'AdminToolsSectionNavigationRenderer::render('
        );

        self::assertNotFalse($headerposition);
        self::assertNotFalse($navposition);
        self::assertGreaterThan(
            $headerposition,
            $navposition
        );
    }

    public function test_secondary_navigation_follows_header_on_subpages(): void {
        foreach ([
            'admin/tools/action.php',
            'admin/tools/history.php',
        ] as $file) {
            $content = $this->file($file);

            $headerposition = strpos(
                $content,
                'CrmPageHeader::render('
            );
            $navposition = strpos(
                $content,
                'AdminToolsSectionNavigationRenderer::render('
            );

            self::assertNotFalse($headerposition);
            self::assertNotFalse($navposition);
            self::assertGreaterThan(
                $headerposition,
                $navposition
            );
        }
    }
}
