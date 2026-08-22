<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o11_1_preview_constructor_fix_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_preview_and_thread_supply_account_repository(): void {
        foreach (
            [
                'ajax/inbox_thread_preview.php',
                'admin/inbox/thread.php',
            ]
            as $relative
        ) {
            $source = $this->file($relative);

            self::assertStringContainsString(
                'new InboxAccountRepository()',
                $source
            );

            self::assertStringContainsString(
                'new InboxReadService(',
                $source
            );
        }
    }
}
