<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox;

defined('MOODLE_INTERNAL') || die();

final class commerce_796_inbox_thread_action_links_selected_state_test extends \advanced_testcase {

    public function test_thread_actions_are_presented_as_left_aligned_icon_links(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );
        $styles = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles.css'
        );

        $this->assertStringContainsString('crm-inbox-thread-action-link', $renderer);
        $this->assertStringContainsString('crm-inbox-thread-status-apply', $renderer);
        $this->assertStringContainsString('crm-inbox-thread-action-danger', $renderer);

        $this->assertStringContainsString('.crm-inbox-thread-action-link {', $styles);
        $this->assertStringContainsString('justify-content: flex-start;', $styles);
        $this->assertStringContainsString('background: transparent;', $styles);
        $this->assertStringContainsString('border: 0;', $styles);
        $this->assertStringContainsString('flex-direction: column;', $styles);
        $this->assertStringContainsString('align-items: flex-start;', $styles);
    }

    public function test_selected_thread_uses_pink_state_even_when_unread(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles.css'
        );

        $this->assertStringContainsString(
            '.crm-inbox-thread-card.is-selected.crm-inbox-thread-card-unread',
            $styles
        );
        $this->assertStringContainsString('border-left: 4px solid #e83282 !important;', $styles);
        $this->assertStringContainsString('rgba(255, 235, 244, 1)', $styles);
        $this->assertStringContainsString('#fff8fb 28%', $styles);
    }
}
