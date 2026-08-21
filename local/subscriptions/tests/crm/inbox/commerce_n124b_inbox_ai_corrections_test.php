<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n124b_inbox_ai_corrections_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_translation_failure_preserves_provider_error(): void {
        $dto = $this->file(
            'classes/crm/inbox/ai/dto/InboxTranslationResult.php'
        );
        $service = $this->file(
            'classes/crm/inbox/ai/services/InboxAiPanelService.php'
        );

        self::assertStringContainsString(
            'public readonly ?string $error = null',
            $dto
        );
        self::assertStringContainsString(
            "'error' =>",
            $service
        );
    }

    public function test_analysis_uses_content_sections_not_metric_cards_as_primary_output(): void {
        $renderer = $this->file(
            'classes/crm/inbox/ai/rendering/InboxAiPanelRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-ai-analysis-grid',
            $renderer
        );
        self::assertStringContainsString(
            'crm-inbox-ai-analysis-meta',
            $renderer
        );
    }

    public function test_fallback_summary_extracts_requests_and_questions(): void {
        $provider = $this->file(
            'classes/crm/inbox/ai/providers/fallback/FallbackInboxAiProvider.php'
        );

        self::assertStringContainsString(
            '$customerrequests',
            $provider
        );
        self::assertStringContainsString(
            '$pendingquestions',
            $provider
        );
        self::assertStringContainsString(
            "'customerrequests' =>",
            $provider
        );
    }
}
