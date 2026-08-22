<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;

final class commerce_795o1_5_imap_seen_state_test
    extends \advanced_testcase {

    private function read_state(object $overview): bool {
        $method = new \ReflectionMethod(
            OvhImapConnector::class,
            'overview_is_read'
        );

        return (bool)$method->invoke(
            null,
            $overview
        );
    }

    public function test_unseen_imap_message_is_imported_unread(): void {
        self::assertFalse(
            $this->read_state(
                (object)['seen' => 0]
            )
        );
    }

    public function test_seen_imap_message_is_imported_read(): void {
        self::assertTrue(
            $this->read_state(
                (object)['seen' => 1]
            )
        );
    }

    public function test_missing_seen_flag_defaults_to_unread(): void {
        self::assertFalse(
            $this->read_state(
                (object)[]
            )
        );
    }

    public function test_connector_no_longer_uses_nonexistent_unseen_overview_property(): void {
        $path = dirname(__DIR__, 3)
            . '/classes/crm/inbox/connectors/imap/OvhImapConnector.php';

        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertStringNotContainsString(
            '->unseen',
            $source
        );

        self::assertStringContainsString(
            'overview_is_read($overview)',
            $source
        );

        self::assertStringContainsString(
            'overview_is_read($item)',
            $source
        );
    }

    public function test_o14_recent_first_bootstrap_is_preserved(): void {
        $path = dirname(__DIR__, 3)
            . '/classes/crm/inbox/connectors/imap/OvhImapConnector.php';

        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertStringContainsString(
            '? array_slice($uids, -$limit)',
            $source
        );

        self::assertStringContainsString(
            '!$initialsync',
            $source
        );
    }
}
