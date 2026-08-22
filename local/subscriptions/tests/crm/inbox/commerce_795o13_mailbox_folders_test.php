<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o13_mailbox_folders_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_semantic_folder_navigation_exists(): void {
        $criteria = $this->file(
            'classes/crm/inbox/dto/InboxThreadCriteria.php'
        );

        self::assertStringContainsString(
            'ALLOWED_FOLDERS',
            $criteria
        );

        self::assertStringContainsString(
            'with_folder(',
            $criteria
        );

        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );

        self::assertStringContainsString(
            'crm-inbox-folder-navigation',
            $renderer
        );
    }

    public function test_folder_mapping_uses_mailbox_configuration(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );

        self::assertStringContainsString(
            'configured_folder_names(',
            $repository
        );

        self::assertStringContainsString(
            "configuration['folders']",
            $repository
        );

        self::assertStringContainsString(
            't.locallydeleted = 1',
            $repository
        );
    }

    public function test_trash_can_be_restored_to_inbox(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxThreadActionService.php'
        );

        self::assertStringContainsString(
            'restore_to_inbox(',
            $service
        );

        self::assertStringContainsString(
            "'inbox'",
            $service
        );

        $bulk = $this->file(
            'classes/crm/inbox/services/InboxBulkActionService.php'
        );

        self::assertStringContainsString(
            "'restore'",
            $bulk
        );
    }

    public function test_count_query_joins_account_for_o11_search(): void {
        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );

        self::assertStringContainsString(
            'LEFT JOIN {local_subscriptions_inbox_account} account',
            $repository
        );
    }
}
