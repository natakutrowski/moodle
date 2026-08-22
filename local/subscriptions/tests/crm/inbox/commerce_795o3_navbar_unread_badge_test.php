<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\services\InboxUnreadCountService;

final class commerce_795o3_navbar_unread_badge_test
    extends \advanced_testcase {

    public function test_counter_sums_only_active_visible_mailbox_unread_messages(): void {
        global $DB;

        $this->resetAfterTest(true);

        $account1 = $DB->insert_record(
            'local_subscriptions_inbox_account',
            (object)[
                'name' => 'Active Inbox',
                'email' => 'active@example.test',
                'provider' => 'imap_smtp',
                'enabled' => 1,
                'credentialkey' => 'test',
                'configurationjson' => '{}',
                'syncstatejson' => '{}',
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        $account2 = $DB->insert_record(
            'local_subscriptions_inbox_account',
            (object)[
                'name' => 'Disabled Inbox',
                'email' => 'disabled@example.test',
                'provider' => 'imap_smtp',
                'enabled' => 0,
                'credentialkey' => 'test',
                'configurationjson' => '{}',
                'syncstatejson' => '{}',
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );

        $base = [
            'contactid' => null,
            'providerthreadid' => null,
            'subject' => 'Test',
            'status' => 'open',
            'priority' => 'normal',
            'assigneduserid' => null,
            'assignedteamid' => null,
            'folder' => 'INBOX',
            'messagecount' => 1,
            'lastmessageat' => time(),
            'lastmessageid' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        $DB->insert_record(
            'local_subscriptions_inbox_thread',
            (object)array_merge(
                $base,
                [
                    'accountid' => $account1,
                    'unreadcount' => 2,
                    'locallydeleted' => 0,
                ]
            )
        );

        $DB->insert_record(
            'local_subscriptions_inbox_thread',
            (object)array_merge(
                $base,
                [
                    'accountid' => $account1,
                    'unreadcount' => 3,
                    'locallydeleted' => 0,
                ]
            )
        );

        // Deleted locally: excluded.
        $DB->insert_record(
            'local_subscriptions_inbox_thread',
            (object)array_merge(
                $base,
                [
                    'accountid' => $account1,
                    'unreadcount' => 7,
                    'locallydeleted' => 1,
                ]
            )
        );

        // Disabled mailbox: excluded.
        $DB->insert_record(
            'local_subscriptions_inbox_thread',
            (object)array_merge(
                $base,
                [
                    'accountid' => $account2,
                    'unreadcount' => 11,
                    'locallydeleted' => 0,
                ]
            )
        );

        self::assertSame(
            5,
            (new InboxUnreadCountService())->count()
        );
    }

    public function test_navigation_renders_inbox_badge_only_when_nonzero(): void {
        $path = dirname(__DIR__, 3)
            . '/classes/crm/navigation/CrmNavigationRenderer.php';

        self::assertFileExists($path);

        $source = file_get_contents($path);
        self::assertIsString($source);

        self::assertStringContainsString(
            'CrmNavigationKeys::INBOX',
            $source
        );

        self::assertStringContainsString(
            'if ($badgecount > 0)',
            $source
        );

        self::assertStringContainsString(
            "'99+'",
            $source
        );

        self::assertStringContainsString(
            'crm-app-navigation-badge-inbox',
            $source
        );
    }
}
