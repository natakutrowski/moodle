<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_3_reply_compose_recipient_ux_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_reply_uses_form_back_button_and_locked_subject(): void {
        $reply = $this->file('admin/inbox/reply.php');

        self::assertStringNotContainsString(
            'CrmBackLinkRenderer',
            $reply
        );
        self::assertStringContainsString(
            'crm-inbox-o16-reply-back',
            $reply
        );
        self::assertStringContainsString(
            "'readonly' => 'readonly'",
            $reply
        );
        self::assertStringContainsString(
            'data-inbox-subject-toggle',
            $reply
        );
    }

    public function test_reply_and_compose_use_shared_recipient_picker(): void {
        foreach (
            ['admin/inbox/reply.php', 'admin/inbox/compose.php']
            as $page
        ) {
            self::assertStringContainsString(
                'InboxRecipientPickerRenderer::render',
                $this->file($page)
            );
        }

        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRecipientPickerRenderer.php'
        );
        self::assertStringContainsString(
            'data-inbox-recipient-picker',
            $renderer
        );
        self::assertStringContainsString(
            'inbox_recipient_search.php',
            $renderer
        );
    }

    public function test_recipient_search_is_user360_aware_and_capability_protected(): void {
        $endpoint = $this->file(
            'ajax/inbox_recipient_search.php'
        );
        self::assertStringContainsString(
            'Capabilities::MANAGE_INBOX',
            $endpoint
        );
        self::assertStringContainsString(
            'UserSearchRepository',
            $endpoint
        );
        self::assertStringContainsString(
            "'user360url'",
            $endpoint
        );
    }

    public function test_composer_buttons_have_icons_and_picker_runtime(): void {
        $reply = $this->file('admin/inbox/reply.php');
        self::assertStringContainsString('fa fa-save me-1', $reply);
        self::assertStringContainsString('fa fa-paper-plane me-1', $reply);
        self::assertStringContainsString('fa fa-image me-1', $reply);

        $amd = $this->file('amd/src/inbox_ui.js');
        self::assertStringContainsString('initRecipientPickers', $amd);
        self::assertStringContainsString('searchRecipients', $amd);
        self::assertStringContainsString('handleSubjectToggle', $amd);
    }

    public function test_thread_utility_actions_are_stacked(): void {
        $css = $this->file('styles.css');
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, 1fr) !important;',
            $css
        );
    }
}
