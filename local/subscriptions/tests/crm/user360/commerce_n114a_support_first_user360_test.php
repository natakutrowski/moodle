<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();

final class commerce_n114a_support_first_user360_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_profile_service_exposes_course_progress_and_levelup_scope(): void {
        $service = $this->file('classes/service/UserProfileService.php');
        $model = $this->file('classes/crm/user/UserProfileViewModel.php');
        $xp = $this->file('classes/crm/success/repositories/LevelUpXpRepository.php');
        self::assertStringContainsString('build_learning_progress(', $service);
        self::assertStringContainsString('MoodleCourseProgressRepository', $service);
        self::assertStringContainsString('get_course_scope_records', $service);
        self::assertStringContainsString('public readonly array $learningprogress', $model);
        self::assertStringContainsString("'learningprogress' =>", $model);
        self::assertStringContainsString('public function get_course_scope_records', $xp);
        self::assertStringContainsString("'scope' => 'none'", $xp);
        self::assertStringContainsString("? 'site'", $xp);
        self::assertStringContainsString(": 'course'", $xp);
    }

    public function test_first_screen_is_support_first(): void {
        $renderer = $this->file('classes/crm/user360/rendering/User360SupportOverviewRenderer.php');
        foreach (['purchases($profile)', 'learning($profile)', 'communication($profile)', 'recent_activity($profile)', 'support_actions($profile)'] as $expected) {
            self::assertStringContainsString($expected, $renderer);
        }
    }

    public function test_workspace_separates_support_view_from_advanced_crm(): void {
        $renderer = $this->file('classes/crm/user360/workspace/User360WorkspaceRenderer.php');
        self::assertStringContainsString('User360SupportOverviewRenderer::render(', $renderer);
        self::assertStringContainsString('User360AdvancedRenderer::render(', $renderer);
        self::assertStringNotContainsString('crm-user360-n114-advanced-summary', $renderer);
        self::assertStringNotContainsString('User360OverviewRenderer::render_overview(', $renderer);
    }

    public function test_support_hero_owns_service_client_kpis(): void {
        $factory = $this->file('classes/crm/user360/workspace/User360WorkspaceFactory.php');
        $renderer = $this->file('classes/crm/user360/rendering/User360SupportOverviewRenderer.php');
        self::assertStringContainsString('User360SupportOverviewRenderer::render_hero(', $factory);
        self::assertStringContainsString('crm-user360-n114b-hero-kpis', $renderer);
    }

    public function test_n114a_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
