<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\workspace\InboxThreadWorkspaceFactory;
use local_subscriptions\crm\inbox\workspace\InboxWorkspaceFactory;
use local_subscriptions\dashboard\workspace\DashboardWorkspaceFactory;

/**
 * Tests the generic CRM Workspace registry.
 *
 * @covers \local_subscriptions\crm\workspace\WorkspaceRegistry
 */
final class WorkspaceRegistryTest
    extends \advanced_testcase {

    public function test_registered_keys_are_stable():
        void {
        $this->assertSame(
            [
                DashboardWorkspaceFactory::WORKSPACE_KEY,
                InboxWorkspaceFactory::WORKSPACE_KEY,
                InboxThreadWorkspaceFactory::WORKSPACE_KEY,
            ],
            WorkspaceRegistry::keys()
        );
    }

    public function test_registry_recognizes_known_workspaces():
        void {
        foreach (
            WorkspaceRegistry::keys()
            as $workspace
        ) {
            $this->assertTrue(
                WorkspaceRegistry::has($workspace)
            );
        }

        $this->assertFalse(
            WorkspaceRegistry::has('unknown')
        );
    }

    public function test_registry_returns_expected_capabilities():
        void {
        $this->assertSame(
            Capabilities::VIEW_DASHBOARD,
            WorkspaceRegistry::capability(
                WorkspaceRegistry::DASHBOARD
            )
        );

        $this->assertSame(
            Capabilities::VIEW_INBOX,
            WorkspaceRegistry::capability(
                WorkspaceRegistry::INBOX
            )
        );

        $this->assertSame(
            Capabilities::VIEW_INBOX,
            WorkspaceRegistry::capability(
                WorkspaceRegistry::INBOX_THREAD
            )
        );
    }

    public function test_registry_returns_expected_preference_keys():
        void {
        $this->assertSame(
            DashboardWorkspaceFactory::PREFERENCE_KEY,
            WorkspaceRegistry::preference_key(
                WorkspaceRegistry::DASHBOARD
            )
        );

        $this->assertSame(
            InboxWorkspaceFactory::PREFERENCE_KEY,
            WorkspaceRegistry::preference_key(
                WorkspaceRegistry::INBOX
            )
        );

        $this->assertSame(
            InboxThreadWorkspaceFactory::PREFERENCE_KEY,
            WorkspaceRegistry::preference_key(
                WorkspaceRegistry::INBOX_THREAD
            )
        );
    }

    public function test_unknown_workspace_is_rejected():
        void {
        $this->expectException(
            \invalid_parameter_exception::class
        );

        WorkspaceRegistry::normalize_key(
            'unknown_workspace'
        );
    }

    public function test_dashboard_preference_definition_is_created():
        void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()
            ->create_user();

        $definition =
            WorkspaceRegistry::
                definition_for_preferences(
                    WorkspaceRegistry::DASHBOARD,
                    (int)$user->id
                );

        $this->assertSame(
            DashboardWorkspaceFactory::WORKSPACE_KEY,
            $definition->key
        );

        $this->assertSame(
            DashboardWorkspaceFactory::PREFERENCE_KEY,
            $definition->preferencekey
        );
    }

    public function test_inbox_preference_definitions_are_created():
        void {
        $inbox =
            WorkspaceRegistry::
                definition_for_preferences(
                    WorkspaceRegistry::INBOX,
                    0
                );

        $thread =
            WorkspaceRegistry::
                definition_for_preferences(
                    WorkspaceRegistry::INBOX_THREAD,
                    0
                );

        $this->assertSame(
            InboxWorkspaceFactory::WORKSPACE_KEY,
            $inbox->key
        );

        $this->assertSame(
            InboxThreadWorkspaceFactory::WORKSPACE_KEY,
            $thread->key
        );
    }
}