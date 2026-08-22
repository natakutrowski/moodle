<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_3_5_regression_guard_test extends \advanced_testcase {

    public function test_o1632_index_ux_and_o1634_sync_are_kept_together(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/rendering/InboxRenderer.php'
        );
        $styles = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles.css'
        );

        $this->assertStringContainsString("'custom' => get_string(", $renderer);
        $this->assertStringContainsString("'crm_inbox_period_custom_o16_3_2'", $renderer);
        $this->assertStringContainsString("'name' => 'datefrom'", $renderer);
        $this->assertStringContainsString("'name' => 'dateto'", $renderer);
        $this->assertStringContainsString("'fa fa-filter'", $renderer);
        $this->assertStringContainsString("'fa fa-filter me-1'", $renderer);
        $this->assertStringContainsString('crm_inbox_last_sync_o1634', $renderer);

        $this->assertStringContainsString('.crm-breadcrumb-item:not(:last-child)::after', $styles);
        $this->assertStringContainsString('transform: rotate(45deg);', $styles);
        $this->assertStringContainsString('.crm-inbox-filter-details-summary::after', $styles);
        $this->assertStringNotContainsString('content: "›";', $styles);
        $this->assertStringNotContainsString('content: "⌄";', $styles);
    }

    public function test_internal_navigation_exposes_inbox_and_draft_counters(): void {
        global $CFG;

        $navigation = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/rendering/InboxSectionNavigationRenderer.php'
        );

        $this->assertStringContainsString('InboxUnreadCountService', $navigation);
        $this->assertStringContainsString('count_compose_drafts', $navigation);
        $this->assertStringContainsString("'count' => \$unreadcount", $navigation);
        $this->assertStringContainsString("'count' => \$draftcount", $navigation);
        $this->assertStringContainsString('crm-inbox-o15-nav-count', $navigation);
    }
}
