<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n127a1_work_runtime_history_fix_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_team_status_uses_plugin_strings(): void {
        $page = $this->file('admin/work/teams.php');

        self::assertStringContainsString(
            'crm_work_team_enabled_n127a1',
            $page
        );
        self::assertStringContainsString(
            'crm_work_team_disabled_n127a1',
            $page
        );
        self::assertStringNotContainsString(
            "? 'enabled'",
            $page
        );
    }

    public function test_history_actions_are_presented_with_labels(): void {
        $renderer = $this->file(
            'classes/crm/work/rendering/WorkItemRenderer.php'
        );

        foreach ([
            "'created'",
            "'comment_added'",
            "'status_changed'",
            "'priority_changed'",
            "'assignment_changed'",
            "'link_added'",
        ] as $action) {
            self::assertStringContainsString(
                $action,
                $renderer
            );
        }

        self::assertStringContainsString(
            'history_action_label(',
            $renderer
        );
    }
}
