<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests the generic CRM Workspace preference service.
 *
 * @covers \local_subscriptions\crm\workspace\WorkspacePreferenceService
 * @covers \local_subscriptions\crm\workspace\WorkspaceLayout
 * @covers \local_subscriptions\crm\workspace\WorkspaceDefinition
 */
final class workspace_preference_service_test
    extends advanced_testcase {

    /**
     * Creates a small test Workspace definition.
     */
    private function definition(): WorkspaceDefinition {
        $definition = new WorkspaceDefinition(
            'test-workspace',
            'local_subscriptions_test_workspace',
            [
                'hero',
                'main',
                'side',
            ]
        );

        $definition->register(
            new WorkspaceItemDefinition(
                key: 'stats',
                label: 'Stats',
                description: 'Stats description',
                icon: 'S',
                zone: 'hero',
                span: 3,
                type: WorkspaceItemDefinition::TYPE_CARD,
                hideable: true,
                movable: true,
                defaultvisible: true,
                renderer: static fn(): string => 'Stats'
            )
        );

        $definition->register(
            new WorkspaceItemDefinition(
                key: 'inbox',
                label: 'Inbox',
                description: 'Inbox description',
                icon: 'I',
                zone: 'main',
                span: 2,
                type: WorkspaceItemDefinition::TYPE_CARD,
                hideable: true,
                movable: true,
                defaultvisible: true,
                renderer: static fn(): string => 'Inbox'
            )
        );

        $definition->register(
            new WorkspaceItemDefinition(
                key: 'activity',
                label: 'Activity',
                description: 'Activity description',
                icon: 'A',
                zone: 'main',
                span: 1,
                type: WorkspaceItemDefinition::TYPE_CARD,
                hideable: true,
                movable: true,
                defaultvisible: false,
                renderer: static fn(): string => 'Activity'
            )
        );

        $definition->register(
            new WorkspaceItemDefinition(
                key: 'team',
                label: 'Team',
                description: 'Team description',
                icon: 'T',
                zone: 'side',
                span: 1,
                type: WorkspaceItemDefinition::TYPE_WIDGET,
                hideable: false,
                movable: false,
                defaultvisible: true,
                renderer: static fn(): string => 'Team'
            )
        );

        return $definition;
    }

    public function test_defaults_use_registered_order(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->defaults();

        $this->assertSame(
            ['stats'],
            $layout->order['hero']
        );

        $this->assertSame(
            ['inbox', 'activity'],
            $layout->order['main']
        );

        $this->assertSame(
            ['team'],
            $layout->order['side']
        );

        $this->assertSame(
            ['activity'],
            $layout->hidden
        );
    }

    public function test_version_one_layout_is_supported(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 1,
            'hidden' => ['inbox'],
            'order' => [
                'hero' => ['stats'],
                'main' => ['inbox', 'activity'],
                'side' => ['team'],
            ],
        ]);

        $this->assertTrue(
            $layout->is_hidden('inbox')
        );

        $this->assertSame(
            WorkspaceLayout::VERSION,
            $layout->to_array()['version']
        );
    }

    public function test_unknown_items_are_removed(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 2,
            'hidden' => [
                'unknown',
                'inbox',
            ],
            'order' => [
                'hero' => [
                    'stats',
                    'unknown',
                ],
                'main' => [
                    'unknown',
                    'inbox',
                    'activity',
                ],
                'side' => ['team'],
            ],
        ]);

        $this->assertSame(
            ['inbox'],
            $layout->hidden
        );

        $this->assertNotContains(
            'unknown',
            $layout->order['hero']
        );

        $this->assertNotContains(
            'unknown',
            $layout->order['main']
        );
    }

    public function test_items_cannot_move_to_another_zone(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 2,
            'hidden' => [],
            'order' => [
                'hero' => [
                    'stats',
                    'inbox',
                ],
                'main' => [
                    'team',
                    'activity',
                ],
                'side' => [],
            ],
        ]);

        $this->assertSame(
            ['stats'],
            $layout->order['hero']
        );

        $this->assertSame(
            ['activity', 'inbox'],
            $layout->order['main']
        );

        $this->assertSame(
            ['team'],
            $layout->order['side']
        );
    }

    public function test_duplicate_items_are_removed(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 2,
            'hidden' => [],
            'order' => [
                'hero' => [
                    'stats',
                    'stats',
                ],
                'main' => [
                    'inbox',
                    'inbox',
                    'activity',
                ],
                'side' => [
                    'team',
                    'team',
                ],
            ],
        ]);

        $this->assertSame(
            ['stats'],
            $layout->order['hero']
        );

        $this->assertSame(
            ['inbox', 'activity'],
            $layout->order['main']
        );

        $this->assertSame(
            ['team'],
            $layout->order['side']
        );
    }

    public function test_non_hideable_item_cannot_be_hidden(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 2,
            'hidden' => [
                'team',
                'inbox',
            ],
            'order' => [
                'hero' => ['stats'],
                'main' => [
                    'inbox',
                    'activity',
                ],
                'side' => ['team'],
            ],
        ]);

        $this->assertSame(
            ['inbox'],
            $layout->hidden
        );
    }

    public function test_new_default_hidden_item_is_appended_and_hidden(): void {
        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $layout = $service->normalize([
            'version' => 1,
            'hidden' => [],
            'order' => [
                'hero' => ['stats'],
                'main' => ['inbox'],
                'side' => ['team'],
            ],
        ]);

        $this->assertSame(
            ['inbox', 'activity'],
            $layout->order['main']
        );

        $this->assertTrue(
            $layout->is_hidden('activity')
        );
    }

    public function test_save_and_reset_use_user_preferences(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()
            ->create_user();

        $service = new WorkspacePreferenceService(
            $this->definition()
        );

        $saved = $service->save(
            [
                'version' => 2,
                'hidden' => ['inbox'],
                'order' => [
                    'hero' => ['stats'],
                    'main' => [
                        'activity',
                        'inbox',
                    ],
                    'side' => ['team'],
                ],
            ],
            (int)$user->id
        );

        $this->assertTrue(
            $saved->is_hidden('inbox')
        );

        $loaded = $service->load(
            (int)$user->id
        );

        $this->assertSame(
            ['activity', 'inbox'],
            $loaded->order['main']
        );

        $reset = $service->reset(
            (int)$user->id
        );

        $this->assertSame(
            ['inbox', 'activity'],
            $reset->order['main']
        );

        $this->assertSame(
            ['activity'],
            $reset->hidden
        );
    }
}