<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox;

use advanced_testcase;

final class commerce_795o16_3_4_secondary_pages_ux_test extends advanced_testcase {
    public function test_secondary_pages_and_sync_ux_are_present(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $compose = file_get_contents($root . 'admin/inbox/compose.php');
        $drafts = file_get_contents($root . 'admin/inbox/drafts.php');
        $templates = file_get_contents($root . 'admin/inbox/templates.php');
        $renderer = file_get_contents($root . 'classes/crm/inbox/rendering/InboxRenderer.php');

        $this->assertStringNotContainsString('CrmBackLinkRenderer::render', $compose);
        $this->assertStringNotContainsString('CrmBackLinkRenderer::render', $drafts);
        $this->assertStringNotContainsString('CrmBackLinkRenderer::render', $templates);

        $this->assertStringContainsString('fa fa-pencil me-1', $drafts);
        $this->assertStringContainsString('fa fa-trash me-1', $drafts);
        $this->assertStringContainsString('crm-inbox-secondary-action', $drafts);

        $this->assertStringContainsString('fa fa-pencil me-1', $templates);
        $this->assertStringContainsString('fa fa-trash me-1', $templates);
        $this->assertStringContainsString('fa fa-save me-1', $templates);
        $this->assertStringContainsString("'btn btn-sm btn-primary'", $templates);

        $this->assertStringContainsString('InboxAccountRepository', $renderer);
        $this->assertStringContainsString('crm_inbox_last_sync_o1634', $renderer);
        $this->assertStringContainsString('crm-inbox-last-sync', $renderer);
    }
}
