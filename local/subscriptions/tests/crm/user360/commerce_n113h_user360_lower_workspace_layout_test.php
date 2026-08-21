<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113h_user360_lower_workspace_layout_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    private function method(string $source, string $name): string {
        $start = strpos($source, 'private static function ' . $name);
        self::assertNotFalse($start);
        $end = strpos($source, "\n    private static function ", $start + 10);
        if ($end === false) {
            $end = strlen($source);
        }
        return substr($source, $start, $end - $start);
    }

    public function test_commerce_and_identities_use_sidebar(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );

        foreach (['register_commercial', 'register_identities'] as $method) {
            $fragment = $this->method($factory, $method);
            self::assertStringContainsString(
                'zone: self::ZONE_SIDEBAR',
                $fragment
            );
            self::assertStringContainsString(
                'span: 1',
                $fragment
            );
        }
    }

    public function test_timeline_uses_main_two_thirds_column(): void {
        $factory = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceFactory.php'
        );
        $fragment = $this->method($factory, 'register_timeline');

        self::assertStringContainsString(
            'zone: self::ZONE_MAIN',
            $fragment
        );
        self::assertStringContainsString(
            'span: 2',
            $fragment
        );
        self::assertStringNotContainsString(
            'zone: self::ZONE_TIMELINE',
            $fragment
        );
    }

    public function test_renderer_uses_dedicated_advanced_hub(): void {
        $renderer = $this->file(
            'classes/crm/user360/workspace/User360WorkspaceRenderer.php'
        );

        self::assertStringContainsString(
            'User360AdvancedRenderer::render(',
            $renderer
        );
        self::assertStringNotContainsString(
            "'crm-user360-workspace-timeline-zone'",
            $renderer
        );
    }

    public function test_lower_workspace_has_explicit_two_thirds_one_third_css(): void {
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-user360-workspace-layout.has-main.has-sidebar',
            $styles
        );
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, 2fr) minmax(20rem, 1fr);',
            $styles
        );
        self::assertStringContainsString(
            '.crm-user360-workspace-sidebar',
            $styles
        );
        self::assertStringContainsString(
            '.crm-user360-n113b-metrics',
            $styles
        );
    }

    public function test_n113h_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
