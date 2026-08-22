<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_1_1_index_micro_polish_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_workspace_uses_more_vertical_viewport(): void {
        $css = $this->file('styles.css');

        self::assertStringContainsString(
            'calc(100vh - 15rem)',
            $css
        );

        self::assertStringContainsString(
            'min-height: 40rem',
            $css
        );
    }

    public function test_preview_actions_have_breathing_room(): void {
        $css = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-inbox-preview-open',
            $css
        );

        self::assertStringContainsString(
            'margin-right: .45rem',
            $css
        );

        self::assertStringContainsString(
            '.crm-inbox-preview-management',
            $css
        );

        self::assertStringContainsString(
            'margin-bottom: .65rem',
            $css
        );
    }

    public function test_context_badges_are_horizontal_and_soft_coloured(): void {
        $css = $this->file('styles.css');

        self::assertStringContainsString(
            'flex-direction: row',
            $css
        );

        self::assertStringContainsString(
            'background: #fff0f6 !important',
            $css
        );

        self::assertStringContainsString(
            'background: #eef1f5 !important',
            $css
        );
    }

    public function test_context_does_not_force_an_internal_scrollbar(): void {
        $css = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-workspace-item-content',
            $css
        );

        self::assertStringContainsString(
            'overflow: visible',
            $css
        );
    }
}
