<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o15_global_ux_refactor_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_shared_navigation_is_used_across_inbox_pages(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxSectionNavigationRenderer.php'
        );

        foreach (
            [
                'admin_inbox_page',
                'admin_inbox_compose_page',
                'admin_inbox_drafts_page',
                'admin_inbox_templates_page',
                'admin_inbox_diagnostics_page',
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $renderer
            );
        }

        foreach (
            [
                'admin/inbox/index.php',
                'admin/inbox/compose.php',
                'admin/inbox/reply.php',
                'admin/inbox/thread.php',
                'admin/inbox/drafts.php',
                'admin/inbox/templates.php',
                'admin/inbox/diagnostics.php',
            ]
            as $relative
        ) {
            self::assertStringContainsString(
                'InboxSectionNavigationRenderer::render',
                $this->file($relative)
            );
        }
    }

    public function test_index_has_bounded_desktop_split_view(): void {
        $workspace = $this->file(
            'classes/crm/inbox/workspace/InboxWorkspaceRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-o15-workspace',
            $workspace
        );

        $css = $this->file('styles.css');

        self::assertStringContainsString(
            'calc(100vh - 26rem)',
            $css
        );

        self::assertStringContainsString(
            'overflow-y: auto',
            $css
        );
    }

    public function test_compose_reply_drafts_and_templates_are_rebalanced(): void {
        self::assertStringContainsString(
            'crm-inbox-o15-composer-card',
            $this->file('admin/inbox/compose.php')
        );

        self::assertStringContainsString(
            'crm-inbox-o15-composer-card',
            $this->file('admin/inbox/reply.php')
        );

        self::assertStringContainsString(
            'crm-inbox-o15-draft-card',
            $this->file('admin/inbox/drafts.php')
        );

        self::assertStringContainsString(
            'crm-inbox-o15-template-card',
            $this->file('admin/inbox/templates.php')
        );

        $css = $this->file('styles.css');

        self::assertStringContainsString(
            'width: min(100%, 68rem)',
            $css
        );

        self::assertStringContainsString(
            'min-height: 24rem !important',
            $css
        );
    }

    public function test_shared_navigation_uses_real_html_writer_api(): void {
        $source = $this->file(
            'classes/crm/inbox/rendering/InboxSectionNavigationRenderer.php'
        );

        self::assertStringContainsString(
            "html_writer::tag(\n            'nav'",
            $source
        );
    }
}
