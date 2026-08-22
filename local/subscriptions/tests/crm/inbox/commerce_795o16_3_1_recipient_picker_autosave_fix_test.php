<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_3_1_recipient_picker_autosave_fix_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_recipient_email_regex_is_not_double_escaped(): void {
        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            '/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/',
            $amd
        );

        self::assertStringNotContainsString(
            '/^[^\\\\s@]+@[^\\\\s@]+\\\\.[^\\\\s@]+$/',
            $amd
        );
    }

    public function test_recipient_results_close_on_click_outside_picker(): void {
        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            'handleRecipientOutsideClick',
            $amd
        );

        self::assertStringContainsString(
            'closeRecipientResults(picker);',
            $amd
        );
    }

    public function test_reply_autosave_uses_persisted_thread_account(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxDraftAutosaveService.php'
        );

        self::assertStringContainsString(
            "$" . "replythread = $" . "this->read->get_thread(",
            $service
        );

        self::assertStringContainsString(
            "$" . "accountid =\n                (int)$" . "replythread->accountid;",
            $service
        );

        self::assertStringContainsString(
            "$" . "thread = $" . "replythread;",
            $service
        );
    }

    public function test_compose_autosave_still_requires_enabled_account(): void {
        $service = $this->file(
            'classes/crm/inbox/services/InboxDraftAutosaveService.php'
        );

        self::assertStringContainsString(
            "if (!\$account || !\$account->enabled)",
            $service
        );
    }

    public function test_recipient_picker_supports_keyboard_selection_and_escape(): void {
        $amd = $this->file(
            'amd/src/inbox_ui.js'
        );

        self::assertStringContainsString(
            "event.key === 'ArrowDown'",
            $amd
        );
        self::assertStringContainsString(
            "event.key === 'ArrowUp'",
            $amd
        );
        self::assertStringContainsString(
            "event.key === 'Escape'",
            $amd
        );
        self::assertStringContainsString(
            "event.key === 'Enter' && suggestions.length > 0",
            $amd
        );
        self::assertStringContainsString(
            "suggestion.dataset.email || ''",
            $amd
        );

        $styles = $this->file('styles.css');
        self::assertStringContainsString(
            '.crm-inbox-recipient-result.is-active',
            $styles
        );
    }

    public function test_autosave_endpoint_does_not_require_browser_accountid_for_reply(): void {
        $endpoint = $this->file(
            'admin/inbox/autosave.php'
        );

        self::assertStringContainsString(
            "optional_param(
            'accountid',
            0,
            PARAM_INT",
            $endpoint
        );
    }
}
