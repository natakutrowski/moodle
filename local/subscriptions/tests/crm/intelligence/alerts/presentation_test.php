<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests the central CRM alert presentation layer.
 *
 * @covers \local_subscriptions\crm\intelligence\alerts\CrmAlertPresentation
 */
final class presentation_test extends advanced_testcase {

    public function test_supported_alert_keys_are_known(): void {
        foreach (
            CrmAlertPresentation::keys()
            as $alertkey
        ) {
            $this->assertTrue(
                CrmAlertPresentation::is_known(
                    $alertkey
                )
            );
        }
    }

    public function test_unknown_alert_key_is_not_known(): void {
        $this->assertFalse(
            CrmAlertPresentation::is_known(
                'unknown_alert'
            )
        );
    }

    public function test_known_alert_uses_translated_label(): void {
        $this->assertSame(
            get_string(
                'crm_intelligence_alert_inactive_user',
                'local_subscriptions'
            ),
            CrmAlertPresentation::label(
                'inactive_user'
            )
        );
    }

    public function test_unknown_alert_uses_safe_fallback(): void {
        $this->assertSame(
            get_string(
                'crm_intelligence_alert_fallback',
                'local_subscriptions'
            ),
            CrmAlertPresentation::label(
                'unknown_alert'
            )
        );
    }

    public function test_explicit_valid_severity_is_preserved(): void {
        $this->assertSame(
            'success',
            CrmAlertPresentation::severity(
                'high_risk_user',
                'success'
            )
        );
    }

    public function test_invalid_severity_uses_alert_default(): void {
        $this->assertSame(
            'danger',
            CrmAlertPresentation::severity(
                'high_risk_user',
                'invalid'
            )
        );
    }

    public function test_unknown_alert_uses_info_severity(): void {
        $this->assertSame(
            'info',
            CrmAlertPresentation::severity(
                'unknown_alert'
            )
        );
    }

    public function test_inactive_user_uses_warning_border(): void {
        $this->assertSame(
            'border-warning',
            CrmAlertPresentation::border_class(
                'inactive_user'
            )
        );
    }

    public function test_high_risk_user_has_highest_default_priority(): void {
        $this->assertGreaterThan(
            CrmAlertPresentation::default_priority(
                'trial_without_purchase'
            ),
            CrmAlertPresentation::default_priority(
                'high_risk_user'
            )
        );
    }

    public function test_unknown_alert_uses_default_icon(): void {
        $this->assertSame(
            '🚨',
            CrmAlertPresentation::icon(
                'unknown_alert'
            )
        );
    }

    public function test_priority_level_can_be_critical(): void {
        $this->assertSame(
            'critical',
            CrmAlertPresentation::priority_level(95)
        );
    }

    public function test_priority_level_can_be_high(): void {
        $this->assertSame(
            'high',
            CrmAlertPresentation::priority_level(80)
        );
    }

    public function test_priority_level_can_be_normal(): void {
        $this->assertSame(
            'normal',
            CrmAlertPresentation::priority_level(70)
        );
    }

    public function test_priority_label_is_translated(): void {
        $this->assertSame(
            get_string(
                'crm_intelligence_alert_priority_critical',
                'local_subscriptions'
            ),
            CrmAlertPresentation::priority_label(95)
        );
    }

    public function test_priority_badge_class_is_consistent(): void {
        $this->assertSame(
            'bg-danger',
            CrmAlertPresentation::priority_badge_class(95)
        );

        $this->assertSame(
            'bg-warning text-dark',
            CrmAlertPresentation::priority_badge_class(80)
        );

        $this->assertSame(
            'bg-secondary',
            CrmAlertPresentation::priority_badge_class(70)
        );
    }

    public function test_known_alert_has_recommended_action(): void {
        $this->assertSame(
            get_string(
                'crm_intelligence_alert_next_action_inactive_user',
                'local_subscriptions'
            ),
            CrmAlertPresentation::next_action_label(
                'inactive_user'
            )
        );
    }

    public function test_unknown_alert_uses_default_action(): void {
        $this->assertSame(
            get_string(
                'crm_intelligence_alert_next_action_default',
                'local_subscriptions'
            ),
            CrmAlertPresentation::next_action_label(
                'unknown_alert'
            )
        );
    }

    public function test_invalid_signal_time_returns_null(): void {
        $this->assertNull(
            CrmAlertPresentation::signal_date_label(null)
        );

        $this->assertNull(
            CrmAlertPresentation::signal_age_label(0)
        );
    }

    public function test_signal_age_uses_supplied_current_time(): void {
        $timestamp = 1700000000;
        $now = $timestamp + HOURSECS;

        $this->assertSame(
            get_string(
                'crm_intelligence_alert_signal_age',
                'local_subscriptions',
                format_time(HOURSECS)
            ),
            CrmAlertPresentation::signal_age_label(
                $timestamp,
                $now
            )
        );
    }

}