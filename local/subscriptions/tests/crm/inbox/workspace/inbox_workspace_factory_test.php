<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests the CRM Inbox Workspace foundation.
 *
 * @covers \local_subscriptions\crm\inbox\workspace\InboxWorkspaceFactory
 * @covers \local_subscriptions\crm\inbox\workspace\InboxWorkspaceService
 */
final class inbox_workspace_factory_test
    extends advanced_testcase {

    public function test_workspace_identity_is_stable(): void {
        $definition =
            InboxWorkspaceFactory::create();

        $this->assertSame(
            InboxWorkspaceFactory::WORKSPACE_KEY,
            $definition->key
        );

        $this->assertSame(
            InboxWorkspaceFactory::PREFERENCE_KEY,
            $definition->preferencekey
        );
    }

    public function test_workspace_zones_are_stable(): void {
        $definition =
            InboxWorkspaceFactory::create();

        $this->assertSame(
            [
                InboxWorkspaceFactory::ZONE_NAVIGATION,
                InboxWorkspaceFactory::ZONE_LIST,
                InboxWorkspaceFactory::ZONE_READING,
                InboxWorkspaceFactory::ZONE_CONTEXT,
            ],
            $definition->zones()
        );
    }

    public function test_default_layout_contains_all_zones(): void {
        $service = new InboxWorkspaceService();

        $layout = $service->defaults();

        $this->assertSame(
            [],
            $layout->hidden
        );

        $this->assertSame(
            [
                InboxWorkspaceFactory::ZONE_NAVIGATION => [
                    InboxWorkspaceFactory::ITEM_FILTERS,
                ],
                InboxWorkspaceFactory::ZONE_LIST => [
                    InboxWorkspaceFactory::ITEM_THREAD_LIST,
                ],
                InboxWorkspaceFactory::ZONE_READING => [
                    InboxWorkspaceFactory::ITEM_READING_PLACEHOLDER,
                ],
                InboxWorkspaceFactory::ZONE_CONTEXT => [
                    InboxWorkspaceFactory::ITEM_CONTEXT_PLACEHOLDER,
                ],
            ],
            $layout->order
        );
    }

    public function test_workspace_registers_foundation_items(): void {
        $definition =
            InboxWorkspaceFactory::create_for_preferences();

        $this->assertTrue(
            $definition->has_item(
                InboxWorkspaceFactory::ITEM_FILTERS
            )
        );

        $this->assertTrue(
            $definition->has_item(
                InboxWorkspaceFactory::ITEM_THREAD_LIST
            )
        );
    }

    public function test_foundation_items_use_expected_zones(): void {
        $definition =
            InboxWorkspaceFactory::create_for_preferences();

        $filters = $definition->item(
            InboxWorkspaceFactory::ITEM_FILTERS
        );

        $threads = $definition->item(
            InboxWorkspaceFactory::ITEM_THREAD_LIST
        );

        $this->assertNotNull($filters);
        $this->assertNotNull($threads);

        $this->assertSame(
            InboxWorkspaceFactory::ZONE_NAVIGATION,
            $filters->zone
        );

        $this->assertSame(
            InboxWorkspaceFactory::ZONE_LIST,
            $threads->zone
        );
    }

    public function test_foundation_items_are_not_yet_personalizable():
        void {
        $definition =
            InboxWorkspaceFactory::create_for_preferences();

        foreach (
            [
                InboxWorkspaceFactory::ITEM_FILTERS,
                InboxWorkspaceFactory::ITEM_THREAD_LIST,
            ] as $key
        ) {
            $item = $definition->item($key);

            $this->assertNotNull($item);
            $this->assertFalse($item->hideable);
            $this->assertFalse($item->movable);
            $this->assertTrue(
                $item->defaultvisible
            );
        }
    }

    public function test_default_order_contains_foundation_items():
        void {
        $definition =
            InboxWorkspaceFactory::create_for_preferences();

        $this->assertSame(
            [
                InboxWorkspaceFactory::ZONE_NAVIGATION => [
                    InboxWorkspaceFactory::ITEM_FILTERS,
                ],
                InboxWorkspaceFactory::ZONE_LIST => [
                    InboxWorkspaceFactory::ITEM_THREAD_LIST,
                ],
                InboxWorkspaceFactory::ZONE_READING => [
                    InboxWorkspaceFactory::ITEM_READING_PLACEHOLDER,
                ],
                InboxWorkspaceFactory::ZONE_CONTEXT => [
                    InboxWorkspaceFactory::ITEM_CONTEXT_PLACEHOLDER,
                ],
            ],
            $definition->default_order()
        );
    }

}