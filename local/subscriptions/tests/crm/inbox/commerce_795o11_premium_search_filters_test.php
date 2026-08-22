<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o11_premium_search_filters_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_search_covers_entire_thread_and_metadata(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );

        foreach (
            [
                'searchmessage.bodytext',
                'participant.email',
                'participant.displayname',
                'attachment.filename',
                'account.email',
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $repository
            );
        }

        self::assertStringNotContainsString(
            "implode(' OR ', $" . "searchconditions)"
            . "\n                . implode(' OR ', $" . "searchconditions)",
            $repository
        );
    }

    public function test_criteria_supports_new_premium_filters(): void {
        $criteria = $this->file(
            'classes/crm/inbox/dto/InboxThreadCriteria.php'
        );

        foreach (
            [
                'ALLOWED_READ_STATES',
                'ALLOWED_ATTACHMENT_STATES',
                'ALLOWED_PERIODS',
                'accountid',
                'readstate',
                'attachmentstate',
                'period',
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $criteria
            );
        }
    }

    public function test_repository_applies_filters_server_side(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );

        self::assertStringContainsString(
            "'t.accountid = :filteraccountid'",
            $repository
        );

        self::assertStringContainsString(
            "'t.unreadcount = 0'",
            $repository
        );

        self::assertStringContainsString(
            'attachmentstate',
            $repository
        );

        self::assertStringContainsString(
            'period_start(',
            $repository
        );
    }

    public function test_renderer_exposes_premium_filter_controls(): void {
        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        foreach (
            [
                "'accountid'",
                "'readstate'",
                "'attachments'",
                "'period'",
                'crm_inbox_search_help_o11',
                'crm_inbox_active_filters_o11',
            ]
            as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $renderer
            );
        }
    }

    public function test_read_service_supplies_mailboxes(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxReadService.php'
        );

        $result = $this->file(
            'classes/crm/inbox/dto/InboxThreadListResult.php'
        );

        self::assertStringContainsString(
            'InboxAccountRepository',
            $service
        );

        self::assertStringContainsString(
            '$this->accounts->get_enabled()',
            $service
        );

        self::assertStringContainsString(
            'public readonly array $accounts',
            $result
        );
    }
}
