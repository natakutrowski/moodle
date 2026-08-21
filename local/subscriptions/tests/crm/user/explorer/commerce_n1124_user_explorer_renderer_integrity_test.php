<?php
declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1124_user_explorer_renderer_integrity_test extends \advanced_testcase {
    private function renderer(): string {
        $path = __DIR__
            . '/../../../../classes/crm/user/explorer/UserExplorerRenderer.php';

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_all_internal_render_helpers_called_by_renderer_exist(): void {
        $renderer = $this->renderer();

        preg_match_all(
            '/self::(render_[a-z0-9_]+)\s*\(/i',
            $renderer,
            $calls
        );
        preg_match_all(
            '/private static function\s+(render_[a-z0-9_]+)\s*\(/i',
            $renderer,
            $definitions
        );

        $called = array_values(array_unique($calls[1]));
        $defined = array_values(array_unique($definitions[1]));

        $missing = array_values(array_diff($called, $defined));

        self::assertSame(
            [],
            $missing,
            'UserExplorerRenderer calls undefined helper(s): '
                . implode(', ', $missing)
        );
    }

    public function test_core_user_row_helpers_are_present_after_n112_sales_refactor(): void {
        $renderer = $this->renderer();

        foreach ([
            'render_account_status',
            'render_tags',
            'render_score',
            'render_risk',
            'render_intelligence',
            'render_inbox',
            'render_empty_state',
            'render_pagination',
        ] as $method) {
            self::assertStringContainsString(
                'private static function ' . $method . '(',
                $renderer
            );
        }
    }
}
