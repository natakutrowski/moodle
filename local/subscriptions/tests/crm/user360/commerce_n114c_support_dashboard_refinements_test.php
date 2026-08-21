<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n114c_support_dashboard_refinements_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_user_profile_page_no_longer_renders_top_back_link(): void {
        $view = $this->file('admin/users/view.php');

        self::assertStringNotContainsString(
            'CrmBackLinkRenderer::render(',
            $view
        );
        self::assertStringNotContainsString(
            'use local_subscriptions\\crm\\navigation\\CrmBackLinkRenderer;',
            $view
        );
    }

    public function test_purchase_and_course_titles_are_linked(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'purchase_label_link(',
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/products/view.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/course/view.php'",
            $renderer
        );
    }

    public function test_course_rows_show_latest_activity_instead_of_course_xp(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            '$latestactivity = html_writer::div(',
            $renderer
        );
        self::assertStringContainsString(
            'lastactivityname',
            $renderer
        );

        $learningstart = strpos($renderer, 'private static function learning');
        self::assertNotFalse($learningstart);
        $learningend = strpos($renderer, 'private static function communication', $learningstart);
        self::assertNotFalse($learningend);
        $learning = substr($renderer, $learningstart, $learningend - $learningstart);

        self::assertStringNotContainsString(
            '$xp = html_writer::div(',
            $learning
        );
        self::assertStringContainsString(
            '. $latestactivity',
            $learning
        );
    }

    public function test_progress_repository_exposes_latest_completed_module(): void {
        $repository = $this->file(
            'classes/crm/success/repositories/MoodleCourseProgressRepository.php'
        );

        self::assertStringContainsString(
            'latest_completed_activity',
            $repository
        );
        self::assertStringContainsString(
            "'lastcoursemoduleid'",
            $repository
        );
        self::assertStringContainsString(
            "'lastactivityat'",
            $repository
        );
    }

    public function test_service_resolves_latest_activity_name_with_modinfo(): void {
        $service = $this->file('classes/service/UserProfileService.php');

        self::assertStringContainsString(
            'get_fast_modinfo(',
            $service
        );
        self::assertStringContainsString(
            "'lastactivityname'",
            $service
        );
    }

    public function test_n114c_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
