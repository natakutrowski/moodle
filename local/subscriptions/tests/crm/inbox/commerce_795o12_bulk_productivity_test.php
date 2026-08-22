<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o12_bulk_productivity_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_bulk_service_supports_professional_actions(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxBulkActionService.php'
        );

        foreach (
            [
                "'read'",
                "'unread'",
                "'archive'",
                "'trash'",
                "'status_open'",
                "'status_pending'",
                "'status_resolved'",
                "'status_closed'",
                "'status_spam'",
                "'priority_low'",
                "'priority_normal'",
                "'priority_high'",
                "'priority_urgent'",
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $service
            );
        }
    }

    public function test_bulk_endpoint_preserves_filtered_return_url(): void {
        $endpoint = $this->file(
            'admin/inbox/bulk_action.php'
        );

        self::assertStringContainsString(
            'PARAM_LOCALURL',
            $endpoint
        );

        self::assertStringContainsString(
            'InboxBulkActionService',
            $endpoint
        );

        self::assertStringContainsString(
            'crm_inbox_bulk_partial_o12',
            $endpoint
        );
    }

    public function test_renderer_has_select_all_action_selector_and_count(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        foreach (
            [
                'data-inbox-select-all',
                'data-inbox-bulk-action-select',
                'data-inbox-bulk-apply',
                'data-inbox-bulk-count',
                "'returnurl'",
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $renderer
            );
        }
    }

    public function test_amd_syncs_bulk_selection_and_confirms_trash(): void {
        $js = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'syncBulkToolbar',
            $js
        );

        self::assertStringContainsString(
            "action.value === 'trash'",
            $js
        );

        self::assertStringContainsString(
            'window.confirm(',
            $js
        );
    }
}
