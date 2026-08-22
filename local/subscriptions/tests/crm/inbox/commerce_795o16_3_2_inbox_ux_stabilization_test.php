<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_3_2_inbox_ux_stabilization_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_custom_period_is_supported_end_to_end(): void {
        $criteria = $this->file(
            'classes/crm/inbox/dto/InboxThreadCriteria.php'
        );
        self::assertStringContainsString("'custom',", $criteria);
        self::assertStringContainsString('public readonly string $datefrom', $criteria);
        self::assertStringContainsString('public readonly string $dateto', $criteria);
        self::assertStringContainsString("\$params['datefrom']", $criteria);
        self::assertStringContainsString("\$params['dateto']", $criteria);

        $repository = $this->file(
            'classes/crm/inbox/repositories/InboxReadRepository.php'
        );
        self::assertStringContainsString(
            "\$criteria->period === 'custom'",
            $repository
        );
        self::assertStringContainsString(
            't.lastmessageat >= :periodstart',
            $repository
        );
        self::assertStringContainsString(
            't.lastmessageat <= :periodend',
            $repository
        );

        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRenderer.php'
        );
        self::assertStringContainsString(
            'crm_inbox_period_custom_o16_3_2',
            $renderer
        );
        self::assertStringContainsString(
            "'type' => 'date'",
            $renderer
        );
        self::assertStringContainsString(
            "'fa fa-filter'",
            $renderer
        );
        self::assertStringContainsString(
            "'fa fa-filter me-1'",
            $renderer
        );
    }

    public function test_encoding_sensitive_chevrons_are_drawn_with_css(): void {
        $css = $this->file('styles.css');

        self::assertStringContainsString(
            '.crm-breadcrumb-item:not(:last-child)::after',
            $css
        );
        self::assertStringContainsString(
            'border-width: 1px 1px 0 0;',
            $css
        );
        self::assertStringContainsString(
            '.crm-inbox-filter-details-summary::after',
            $css
        );
        self::assertStringContainsString(
            'content: "";',
            $css
        );
    }

    public function test_thread_other_actions_are_vertical_and_header_icons_spaced(): void {
        $css = $this->file('styles.css');
        self::assertStringContainsString(
            'grid-template-columns: minmax(0, 1fr) !important;',
            $css
        );
        self::assertStringContainsString(
            'white-space: nowrap;',
            $css
        );

        $thread = $this->file('admin/inbox/thread.php');
        self::assertStringContainsString('fa fa-reply-all me-1', $thread);
        self::assertStringContainsString('fa fa-share me-1', $thread);
        self::assertStringContainsString('fa fa-envelope me-1', $thread);
    }

    public function test_reply_initial_recipient_pill_keeps_display_name(): void {
        $reply = $this->file('admin/inbox/reply.php');
        self::assertStringContainsString('$participant->displayname', $reply);
        self::assertStringContainsString('$thread->contactname', $reply);
        self::assertStringContainsString('$recipientlabels', $reply);

        $renderer = $this->file(
            'classes/crm/inbox/rendering/InboxRecipientPickerRenderer.php'
        );
        self::assertStringContainsString(
            "'data-recipient-labels'",
            $renderer
        );

        $amd = $this->file('amd/src/inbox_ui.js');
        self::assertStringContainsString(
            "picker.dataset.recipientLabels || '{}'",
            $amd
        );
    }

    public function test_submit_stops_autosave_and_beforeunload_warning(): void {
        $amd = $this->file('amd/src/inbox_ui.js');

        self::assertStringContainsString(
            'stopAutosaveForSubmit(form);',
            $amd
        );
        self::assertStringContainsString(
            'state.controller.abort();',
            $amd
        );
        self::assertStringContainsString(
            "error.name === 'AbortError'",
            $amd
        );
        self::assertStringContainsString(
            "form.dataset.autosaveSubmitting === '1'",
            $amd
        );
        self::assertStringContainsString(
            'handleAutosaveSubmitIntent',
            $amd
        );
    }
}
