<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o4_3_attachment_storage_mimetype_fix_test
    extends \advanced_testcase {

    private function executable_source(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $source = file_get_contents($path);
        self::assertIsString($source);

        $tokens = token_get_all($source);
        $executable = '';

        foreach ($tokens as $token) {
            if (
                is_array($token)
                && in_array(
                    $token[0],
                    [T_COMMENT, T_DOC_COMMENT],
                    true
                )
            ) {
                continue;
            }

            $executable .= is_array($token)
                ? $token[1]
                : $token;
        }

        return $executable;
    }

    public function test_no_attachment_runtime_uses_nonexistent_param_mimetype(): void {
        foreach (
            [
                'classes/crm/inbox/services/InboxReplyAttachmentService.php',
                'classes/crm/inbox/storage/MoodleFileInboxAttachmentStorage.php',
            ]
            as $relative
        ) {
            self::assertStringNotContainsString(
                'PARAM_MIMETYPE',
                $this->executable_source($relative)
            );
        }
    }

    public function test_storage_uses_safe_mimetype_normalizer(): void {
        $source = $this->executable_source(
            'classes/crm/inbox/storage/MoodleFileInboxAttachmentStorage.php'
        );

        self::assertStringContainsString(
            'normalize_mimetype',
            $source
        );

        self::assertStringContainsString(
            'application/octet-stream',
            $source
        );
    }
}
