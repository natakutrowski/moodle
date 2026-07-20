<?php

namespace local_subscriptions\dashboard\ui;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use moodle_url;

/**
 * Tests the shared CRM Dashboard UI primitives.
 *
 * @covers \local_subscriptions\dashboard\ui\DashboardCardUi
 */
final class dashboard_card_ui_test extends advanced_testcase {

    public function test_shell_contains_common_classes(): void {
        $html = DashboardCardUi::shell(
            content: 'Content',
            extraclasses: 'custom-card'
        );

        $this->assertStringContainsString(
            'local-subscriptions-dashboard-card',
            $html
        );

        $this->assertStringContainsString(
            'crm-dashboard-panel',
            $html
        );

        $this->assertStringContainsString(
            'custom-card',
            $html
        );
    }

    public function test_shell_can_be_labelled(): void {
        $html = DashboardCardUi::shell(
            content: 'Content',
            labelledby: 'card-title'
        );

        $this->assertStringContainsString(
            'aria-labelledby="card-title"',
            $html
        );
    }

    public function test_header_escapes_title_and_subtitle(): void {
        $html = DashboardCardUi::header(
            title: '<Title>',
            icon: '✓',
            subtitle: '<Subtitle>',
            titleid: 'test-title'
        );

        $this->assertStringContainsString(
            '&lt;Title&gt;',
            $html
        );

        $this->assertStringContainsString(
            '&lt;Subtitle&gt;',
            $html
        );

        $this->assertStringContainsString(
            'id="test-title"',
            $html
        );
    }

    public function test_action_uses_supported_style(): void {
        $html = DashboardCardUi::action(
            new moodle_url('/'),
            'Open',
            'success'
        );

        $this->assertStringContainsString(
            'btn-outline-success',
            $html
        );
    }

    public function test_unknown_action_style_falls_back(): void {
        $html = DashboardCardUi::action(
            new moodle_url('/'),
            'Open',
            'unknown'
        );

        $this->assertStringContainsString(
            'btn-outline-primary',
            $html
        );
    }

    public function test_empty_state_has_no_alert_role(): void {
        $html = DashboardCardUi::empty_state(
            title: 'Nothing'
        );

        $this->assertStringNotContainsString(
            'role="alert"',
            $html
        );
    }

    public function test_error_state_has_alert_role(): void {
        $html = DashboardCardUi::error_state(
            title: 'Error'
        );

        $this->assertStringContainsString(
            'role="alert"',
            $html
        );
    }

    public function test_loading_state_is_announced(): void {
        $html = DashboardCardUi::loading_state(
            title: 'Loading'
        );

        $this->assertStringContainsString(
            'aria-live="polite"',
            $html
        );

        $this->assertStringContainsString(
            'aria-busy="true"',
            $html
        );
    }

    public function test_badge_uses_safe_tone(): void {
        $html = DashboardCardUi::badge(
            'Label',
            'invalid'
        );

        $this->assertStringContainsString(
            'crm-dashboard-badge-neutral',
            $html
        );
    }

    public function test_item_uses_shared_class(): void {
        $html = DashboardCardUi::item(
            'Item content',
            'custom-item'
        );

        $this->assertStringContainsString(
            'crm-dashboard-item',
            $html
        );

        $this->assertStringContainsString(
            'custom-item',
            $html
        );
    }
}