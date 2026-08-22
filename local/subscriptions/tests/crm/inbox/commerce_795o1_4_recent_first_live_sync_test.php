<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o1_4_recent_first_live_sync_test
    extends \advanced_testcase {

    private function connector_source(): string {
        $path = dirname(__DIR__, 3)
            . '/classes/crm/inbox/connectors/imap/OvhImapConnector.php';

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_initial_sync_bootstraps_from_newest_uids(): void {
        $content = $this->connector_source();

        self::assertStringContainsString(
            '$initialsync = $cursor === null',
            $content
        );
        self::assertStringContainsString(
            '? array_slice($uids, -$limit)',
            $content
        );
    }

    public function test_incremental_sync_remains_forward_only(): void {
        $content = $this->connector_source();

        self::assertStringContainsString(
            '$uid > $lastuid',
            $content
        );
        self::assertStringContainsString(
            ': array_slice($uids, 0, $limit)',
            $content
        );
    }

    public function test_initial_sync_does_not_request_automatic_historical_backfill(): void {
        $content = $this->connector_source();

        self::assertStringContainsString(
            '!$initialsync',
            $content
        );
        self::assertStringContainsString(
            '&& count($uids) > count($selected)',
            $content
        );
    }
}
