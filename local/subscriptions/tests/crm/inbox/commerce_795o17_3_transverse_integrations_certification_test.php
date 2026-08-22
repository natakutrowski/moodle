<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox;

defined('MOODLE_INTERNAL') || die();

/**
 * O17.3 transverse integration certification guards for CRM Inbox.
 */
final class commerce_795o17_3_transverse_integrations_certification_test extends \advanced_testcase {

    public function test_inbox_capabilities_are_declared_and_used_by_crm_navigation(): void {
        global $CFG;

        $access = file_get_contents($CFG->dirroot . '/local/subscriptions/db/access.php');
        $capabilities = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/admin/Capabilities.php'
        );
        $navigation = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );

        $this->assertStringContainsString("local/subscriptions:view_inbox", $access);
        $this->assertStringContainsString("local/subscriptions:manage_inbox", $access);
        $this->assertStringContainsString('VIEW_INBOX', $capabilities);
        $this->assertStringContainsString('MANAGE_INBOX', $capabilities);
        $this->assertStringContainsString('CrmNavigationKeys::INBOX', $navigation);
        $this->assertStringContainsString('admin_inbox_compose_page()', $navigation);
        $this->assertStringContainsString('admin_inbox_drafts_page()', $navigation);
        $this->assertStringContainsString('admin_inbox_templates_page()', $navigation);
    }

    public function test_crm_navigation_keeps_unread_count_integration(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRenderer.php'
        );

        $this->assertStringContainsString('InboxUnreadCountService', $renderer);
        $this->assertStringContainsString('CrmNavigationKeys::INBOX', $renderer);
        $this->assertStringContainsString('crm_nav_inbox_unread_badge_o3', $renderer);
    }

    public function test_user_explorer_gates_inbox_data_and_links_by_capability(): void {
        global $CFG;

        $index = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/users/index.php'
        );
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/user/explorer/UserExplorerRenderer.php'
        );
        $repository = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/user/explorer/UserExplorerRepository.php'
        );

        $this->assertStringContainsString('Capabilities::VIEW_INBOX', $index);
        $this->assertStringContainsString('without_inbox()', $index);
        $this->assertStringContainsString('admin_inbox_page()', $renderer);
        $this->assertStringContainsString('local_subscriptions_inbox_contact', $repository);
        $this->assertStringContainsString('local_subscriptions_inbox_thread', $repository);
    }

    public function test_thread_to_work_item_integration_keeps_inbox_source(): void {
        global $CFG;

        $threadrenderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/rendering/InboxThreadRenderer.php'
        );

        $this->assertStringContainsString('WorkItemSource::INBOX', $threadrenderer);
        $this->assertStringContainsString('admin_work_item_create_page()', $threadrenderer);
    }

    public function test_all_declared_inbox_scheduled_tasks_have_implementations(): void {
        global $CFG;

        $tasks = file_get_contents($CFG->dirroot . '/local/subscriptions/db/tasks.php');

        $expected = [
            'sync_crm_inbox_task',
            'reconcile_crm_inbox_contacts_task',
            'download_crm_inbox_attachments_task',
            'cleanup_crm_inbox_task',
            'cleanup_crm_inbox_ai_results_task',
            'analyse_crm_inbox_task',
        ];

        foreach ($expected as $task) {
            $this->assertStringContainsString(
                '\\local_subscriptions\\task\\' . $task,
                $tasks,
                'Scheduled task is not declared: ' . $task
            );
            $this->assertFileExists(
                $CFG->dirroot . '/local/subscriptions/classes/task/' . $task . '.php',
                'Scheduled task implementation is missing: ' . $task
            );
        }
    }

    public function test_imap_smtp_and_diagnostics_runtime_wiring_remains_present(): void {
        global $CFG;

        $sync = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/task/sync_crm_inbox_task.php'
        );
        $compose = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/inbox/compose.php'
        );
        $diagnostics = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/inbox/services/InboxDiagnosticsService.php'
        );

        $this->assertStringContainsString('InboxSyncRuntimeFactory', $sync);
        $this->assertStringContainsString('OvhSmtpConnector', $compose);
        $this->assertStringContainsString('MoodleConfigInboxCredentialStore', $compose);
        $this->assertStringContainsString('local_subscriptions_inbox_account', $diagnostics);
        $this->assertStringContainsString('local_subscriptions_inbox_template', $diagnostics);
    }
}
