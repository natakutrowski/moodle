<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o10_conversation_semantics_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_thread_query_exposes_last_message_semantics(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );

        self::assertStringContainsString(
            'lastmessage.direction AS lastdirection',
            $repository
        );

        self::assertStringContainsString(
            'lastmessage.status AS lastmessagestatus',
            $repository
        );
    }

    public function test_thread_cards_render_received_sent_or_draft_badges(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'thread_direction_badge(',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-direction-badge-received',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-direction-badge-sent',
            $renderer
        );

        self::assertStringContainsString(
            'crm-inbox-direction-badge-draft',
            $renderer
        );
    }

    public function test_direction_filter_is_preserved_in_criteria_and_urls(): void {
        $criteria = $this->file(
            'classes/crm/inbox/dto/InboxThreadCriteria.php'
        );

        self::assertStringContainsString(
            'ALLOWED_DIRECTIONS',
            $criteria
        );

        self::assertStringContainsString(
            "$" . "params['direction']",
            $criteria
        );

        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            "'direction'",
            $renderer
        );
    }

    public function test_draft_card_opens_compose_directly(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'admin_inbox_compose_page()',
            $renderer
        );

        self::assertStringContainsString(
            "'DRAFTS'",
            $renderer
        );
    }
}
