<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o6_2_tinymce_editor_test extends \advanced_testcase {
    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_reply_and_compose_use_moodle_editor(): void {
        foreach (['admin/inbox/reply.php', 'admin/inbox/compose.php'] as $file) {
            $content = $this->file($file);
            self::assertStringContainsString('editors_get_preferred_editor(FORMAT_HTML)', $content);
            self::assertStringContainsString('->use_editor(', $content);
            self::assertStringNotContainsString("'contenteditable' => 'true'", $content);
        }
    }

    public function test_inbox_ui_bridges_tinymce_and_cid_pipeline(): void {
        $js = $this->file('amd/src/inbox_ui.js');
        self::assertStringContainsString('window.tinymce.get(editor.id)', $js);
        self::assertStringContainsString('tiny.insertContent', $js);
        self::assertStringContainsString("image.setAttribute('src', 'cid:' + cid)", $js);
        self::assertStringContainsString("tiny.on('input change keyup undo redo SetContent'", $js);
    }
}
