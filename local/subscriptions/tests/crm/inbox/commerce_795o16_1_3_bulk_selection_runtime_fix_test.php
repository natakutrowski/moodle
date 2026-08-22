<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_1_3_bulk_selection_runtime_fix_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_thread_checkboxes_expose_the_selector_expected_by_amd(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            "'data-inbox-thread-select'",
            $renderer
        );

        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            "'[data-inbox-thread-select]'",
            $amd
        );
    }

    public function test_bulk_select_all_and_apply_runtime_are_still_wired(): void {
        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'var handleBulkChange = function(event)',
            $amd
        );

        self::assertStringContainsString(
            'checkbox.checked =',
            $amd
        );

        self::assertStringContainsString(
            'syncBulkToolbar();',
            $amd
        );

        self::assertStringContainsString(
            'apply.disabled =',
            $amd
        );
    }
}
