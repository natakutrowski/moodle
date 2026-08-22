<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o1_1_imap_uid_inspection_runtime_fix_test
    extends \advanced_testcase {

    public function test_imap_state_inspection_does_not_use_unsupported_uid_search_criterion(): void {
        $path = dirname(__DIR__, 3)
            . '/classes/crm/inbox/connectors/imap/OvhImapConnector.php';

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        self::assertStringContainsString(
            "imap_search(\n                \$stream,\n                'ALL',\n                SE_UID",
            $content
        );

        self::assertStringNotContainsString(
            "'UID ' . implode(',', \$chunk)",
            $content
        );

        self::assertStringContainsString(
            'isset($remoteuidset[$uid])',
            $content
        );
    }
}
