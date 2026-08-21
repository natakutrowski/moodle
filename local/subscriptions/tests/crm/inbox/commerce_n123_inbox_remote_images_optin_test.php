<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n123_inbox_remote_images_optin_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_sanitizer_supports_explicit_remote_image_opt_in(): void {
        $file = $this->file('classes/crm/inbox/rendering/InboxHtmlSanitizer.php');
        self::assertStringContainsString('bool $allowremoteimages = false', $file);
        self::assertStringContainsString('data-inbox-load-images', $file);
        self::assertStringContainsString('remove_hidden_email_nodes', $file);
    }

    public function test_ajax_preview_accepts_loadimages_flag(): void {
        $file = $this->file('ajax/inbox_thread_preview.php');
        self::assertStringContainsString("'loadimages'", $file);
        self::assertStringContainsString('PARAM_BOOL', $file);
    }

    public function test_inbox_ui_reloads_preview_when_images_are_requested(): void {
        $file = $this->file('amd/src/inbox_ui.js');
        self::assertStringContainsString('handleLoadRemoteImages', $file);
        self::assertStringContainsString("'loadimages'", $file);
    }
}
