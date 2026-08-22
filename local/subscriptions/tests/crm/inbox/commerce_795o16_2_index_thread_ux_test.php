<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_2_index_thread_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_index_has_compact_filter_actions_and_quick_period(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-o16-period-form',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-filter-apply',
            $renderer
        );

        self::assertStringContainsString(
            'fa fa-filter me-1',
            $renderer
        );

        self::assertStringContainsString(
            'fa fa-undo me-1',
            $renderer
        );
    }

    public function test_thread_uses_standard_page_order_without_back_link(): void {
        $thread = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringNotContainsString(
            'CrmBackLinkRenderer',
            $thread
        );

        $headerpos = strpos(
            $thread,
            'echo CrmPageHeader::render'
        );

        $navpos = strpos(
            $thread,
            'echo InboxSectionNavigationRenderer::render'
        );

        self::assertIsInt($headerpos);
        self::assertIsInt($navpos);
        self::assertLessThan(
            $navpos,
            $headerpos
        );
    }

    public function test_personalization_is_exposed_in_header_through_proxy(): void {
        $thread = $this->file(
            'admin/inbox/thread.php'
        );

        self::assertStringContainsString(
            'open-workspace-personalization-proxy',
            $thread
        );

        $amd = $this->file(
            'amd/src/workspace_personalization.js'
        );

        self::assertStringContainsString(
            'SELECTORS.openProxy',
            $amd
        );

        self::assertStringContainsString(
            'registerProxyEvents',
            $amd
        );
    }

    public function test_thread_actions_use_status_selector_and_utility_grid(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-thread-status-form',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-thread-utility-actions',
            $renderer
        );

        self::assertStringContainsString(
            'fa fa-archive me-1',
            $renderer
        );

        self::assertStringContainsString(
            'fa fa-tasks me-1',
            $renderer
        );
    }

    public function test_bottom_reply_has_icon(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        self::assertStringContainsString(
            'fa fa-reply me-1',
            $renderer
        );
    }
}
