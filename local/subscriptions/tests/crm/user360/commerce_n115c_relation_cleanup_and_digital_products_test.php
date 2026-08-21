<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n115c_relation_cleanup_and_digital_products_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_advanced_relation_no_longer_duplicates_recent_notes(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringNotContainsString(
            'private static function notes(',
            $renderer
        );
        self::assertStringNotContainsString(
            'self::notes($profile)',
            $renderer
        );
    }

    public function test_advanced_actions_exclude_support_quick_actions(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );

        self::assertStringContainsString(
            'expert_actions(',
            $renderer
        );
        self::assertStringContainsString(
            "['email', 'note']",
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115c-action-row',
            $renderer
        );
    }

    public function test_priority_recommendations_have_human_labels(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360RelationRenderer.php'
        );
        $fr = $this->file(
            'lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            'recommendation_list(',
            $renderer
        );
        self::assertStringContainsString(
            '$string[\'crm_intelligence_recommendation_review_customer_success_risk\']',
            $fr
        );
        self::assertStringContainsString(
            '$string[\'crm_intelligence_recommendation_intervene_disengagement_spiral\']',
            $fr
        );
        self::assertStringContainsString(
            '$string[\'crm_intelligence_recommendation_review_learning_difficulty\']',
            $fr
        );
    }

    public function test_support_view_contains_digital_products_under_learning(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        $start = strpos(
            $renderer,
            'public static function render('
        );
        self::assertNotFalse($start);
        $end = strpos(
            $renderer,
            'private static function purchases',
            $start
        );
        self::assertNotFalse($end);
        $method = substr(
            $renderer,
            $start,
            $end - $start
        );

        self::assertStringContainsString(
            '$centre = self::learning($profile)',
            $method
        );
        self::assertStringContainsString(
            '. self::digital_products($profile)',
            $method
        );
    }

    public function test_digital_resources_expose_downloads_and_history(): void {
        $service = $this->file(
            'classes/service/UserProfileService.php'
        );
        $viewmodel = $this->file(
            'classes/crm/user/UserProfileViewModel.php'
        );
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360SupportOverviewRenderer.php'
        );

        self::assertStringContainsString(
            'CommerceDigitalLibraryService',
            $service
        );
        self::assertStringContainsString(
            'digitalresources',
            $viewmodel
        );
        self::assertStringContainsString(
            'downloadcount',
            $renderer
        );
        self::assertStringContainsString(
            'lastdownloaddate',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n115c-download-link',
            $renderer
        );
    }

    public function test_n115c_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
