<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\subscription_config;

/**
 * Tests the unified CRM administrative event presentation.
 *
 * @covers \local_subscriptions\admin\AdminEventPresentation
 */
final class admin_event_presentation_test
    extends advanced_testcase {

    public function test_all_registered_events_have_labels(): void {
        foreach (AdminEvents::all() as $event) {
            $stringkey =
                AdminEventPresentation::
                    string_key($event);

            $this->assertTrue(
                get_string_manager()
                    ->string_exists(
                        $stringkey,
                        'local_subscriptions'
                    ),
                'Missing language string: ' .
                    $stringkey
            );

            $label =
                AdminEventPresentation::label(
                    $event
                );

            $this->assertNotSame(
                '',
                trim($label)
            );
        }
    }

    public function test_all_registered_events_have_icons(): void {
        foreach (AdminEvents::all() as $event) {
            $this->assertNotSame(
                '',
                trim(
                    AdminEventPresentation::icon(
                        $event
                    )
                )
            );
        }
    }

    public function test_all_registered_events_have_categories(): void {
        foreach (AdminEvents::all() as $event) {
            $this->assertNotSame(
                '',
                trim(
                    AdminEventPresentation::
                        category($event)
                )
            );
        }
    }

    public function test_all_registered_events_have_types(): void {
        foreach (AdminEvents::all() as $event) {
            $type =
                AdminEventPresentation::type(
                    $event
                );

            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_]+$/',
                $type
            );
        }
    }

    public function test_work_item_url_is_resolved(): void {
        $log = (object)[
            'objecttype' => 'work_item',
            'objectid' => 42,
            'targetuserid' => 11,
            'details' => null,
        ];

        $url =
            AdminEventPresentation::url($log);

        $this->assertNotNull($url);

        $this->assertStringContainsString(
            subscription_config::
                admin_work_item_view_page(),
            $url->out(false)
        );

        $this->assertStringContainsString(
            'id=42',
            $url->out(false)
        );
    }

    public function test_inbox_thread_url_is_resolved(): void {
        $log = (object)[
            'objecttype' => 'inbox_thread',
            'objectid' => 51,
            'targetuserid' => 11,
            'details' => null,
        ];

        $url =
            AdminEventPresentation::url($log);

        $this->assertNotNull($url);

        $this->assertStringContainsString(
            subscription_config::
                admin_inbox_thread_page(),
            $url->out(false)
        );
    }

    public function test_customer_success_step_uses_plan_id(): void {
        $log = (object)[
            'objecttype' =>
                'customer_success_step',
            'objectid' => 90,
            'targetuserid' => 11,
            'details' => json_encode(
                [
                    'planid' => 12,
                ]
            ),
        ];

        $url =
            AdminEventPresentation::url($log);

        $this->assertNotNull($url);

        $this->assertStringContainsString(
            'id=12',
            $url->out(false)
        );
    }

    public function test_target_user_is_used_as_fallback_url(): void {
        $log = (object)[
            'objecttype' => 'unknown',
            'objectid' => 0,
            'targetuserid' => 99,
            'details' => null,
        ];

        $url =
            AdminEventPresentation::url($log);

        $this->assertNotNull($url);

        $this->assertStringContainsString(
            subscription_config::
                admin_user_view_page(),
            $url->out(false)
        );

        $this->assertStringContainsString(
            'id=99',
            $url->out(false)
        );
    }

    public function test_description_uses_inbox_subject(): void {
        $description =
            AdminEventPresentation::description(
                AdminEvents::
                    INBOX_MESSAGE_RECEIVED,
                [
                    'subject' =>
                        'Question about access',
                ]
            );

        $this->assertSame(
            'Question about access',
            $description
        );
    }

    public function test_description_uses_work_item_reference(): void {
        $description =
            AdminEventPresentation::description(
                AdminEvents::
                    WORK_ITEM_CREATED,
                [
                    'reference' =>
                        'WORK-00042',
                ]
            );

        $this->assertStringContainsString(
            'WORK-00042',
            $description
        );
    }

    public function test_invalid_json_details_return_empty_array(): void {
        $log = (object)[
            'details' => '{invalid',
        ];

        $this->assertSame(
            [],
            AdminEventPresentation::details(
                $log
            )
        );
    }

    public function test_unknown_event_has_safe_fallback(): void {
        $this->assertSame(
            'Unknown custom event',
            AdminEventPresentation::label(
                'unknown.custom_event'
            )
        );

        $this->assertSame(
            '⚙️',
            AdminEventPresentation::icon(
                'unknown.custom_event'
            )
        );

        $this->assertSame(
            'system',
            AdminEventPresentation::category(
                'unknown.custom_event'
            )
        );
    }
}