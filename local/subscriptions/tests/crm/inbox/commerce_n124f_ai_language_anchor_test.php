<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n124f_ai_language_anchor_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_translation_prompt_enforces_selected_language(): void {
        $prompt = $this->file(
            'classes/crm/inbox/ai/prompts/InboxTranslationPromptBuilder.php'
        );

        self::assertStringContainsString(
            'translation-v2',
            $prompt
        );
        self::assertStringContainsString(
            'translatedtext field MUST be written in',
            $prompt
        );
        self::assertStringContainsString(
            'Set targetlanguage exactly to',
            $prompt
        );
    }

    public function test_reply_prompt_enforces_selected_language(): void {
        $prompt = $this->file(
            'classes/crm/inbox/ai/prompts/InboxReplyPromptBuilder.php'
        );

        self::assertStringContainsString(
            'reply-suggestion-v2',
            $prompt
        );
        self::assertStringContainsString(
            'subject and body MUST be written in',
            $prompt
        );
        self::assertStringContainsString(
            'Set the language field exactly to',
            $prompt
        );
    }

    public function test_analysis_summary_prompt_enforces_selected_language(): void {
        $prompt = $this->file(
            'classes/crm/inbox/ai/prompts/InboxSummaryPromptBuilder.php'
        );

        self::assertStringContainsString(
            'summary-v2',
            $prompt
        );
        self::assertStringContainsString(
            'summary, keypoints, pendingquestions and customerrequests MUST all be written in',
            $prompt
        );
    }

    public function test_analysis_action_exposes_language_selector(): void {
        $renderer = $this->file(
            'classes/crm/inbox/ai/rendering/InboxAiPanelRenderer.php'
        );

        $start = strpos(
            $renderer,
            'private static function analysis_action('
        );
        self::assertNotFalse($start);

        $end = strpos(
            $renderer,
            'private static function reply_action(',
            $start
        );
        self::assertNotFalse($end);

        $chunk = substr(
            $renderer,
            $start,
            $end - $start
        );

        self::assertMatchesRegularExpression(
            "/'analyse'.*?true,\\s*false/s",
            $chunk
        );
    }

    public function test_ai_actions_redirect_to_panel_anchor(): void {
        $page = $this->file(
            'admin/inbox/ai_action.php'
        );

        self::assertStringContainsString(
            '$redirecturl->set_anchor(',
            $page
        );
        self::assertStringContainsString(
            "'crm-inbox-ai'",
            $page
        );

        $renderer = $this->file(
            'classes/crm/inbox/ai/rendering/InboxAiPanelRenderer.php'
        );

        self::assertStringContainsString(
            "'id' => 'crm-inbox-ai'",
            $renderer
        );
    }
}
