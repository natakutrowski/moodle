<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Tests the immutable CRM alert DTO.
 *
 * @covers \local_subscriptions\crm\intelligence\alerts\CrmAlert
 */
final class alert_test extends advanced_testcase {

    public function test_minimal_alert_remains_supported(): void {
        $alert = new CrmAlert(
            key: 'inactive_user',
            severity: 'warning',
            priority: 70,
            userid: 42
        );

        $this->assertSame(
            'inactive_user',
            $alert->key
        );

        $this->assertSame(
            42,
            $alert->userid
        );

        $this->assertNull(
            $alert->displayname
        );

        $this->assertFalse(
            $alert->has_user_identity()
        );
    }

    public function test_enriched_alert_exposes_user_and_scores(): void {
        $alert = new CrmAlert(
            key: 'high_risk_user',
            severity: 'danger',
            priority: 95,
            userid: 42,
            displayname: 'Marie Dupont',
            email: 'marie@example.com',
            snapshottime: 1700000000,
            commercialscore: 35,
            engagementscore: 20,
            riskscore: 82,
            globalscore: 31
        );

        $this->assertTrue(
            $alert->has_user_identity()
        );

        $this->assertSame(
            'Marie Dupont',
            $alert->displayname
        );

        $this->assertSame(
            'marie@example.com',
            $alert->email
        );

        $this->assertSame(
            82,
            $alert->riskscore
        );

        $this->assertSame(
            35,
            $alert->commercialscore
        );

        $this->assertSame(
            1700000000,
            $alert->snapshottime
        );
    }

    public function test_alert_can_be_converted_to_object(): void {
        $alert = new CrmAlert(
            key: 'hot_opportunity',
            severity: 'success',
            priority: 75,
            userid: 51,
            displayname: 'Paul Martin',
            email: 'paul@example.com',
            snapshottime: 1700000100,
            commercialscore: 75,
            engagementscore: 68,
            riskscore: 12,
            globalscore: 72
        );

        $object = $alert->to_object();

        $this->assertSame(
            'hot_opportunity',
            $object->key
        );

        $this->assertSame(
            'Paul Martin',
            $object->displayname
        );

        $this->assertSame(
            75,
            $object->commercialscore
        );

        $this->assertSame(
            12,
            $object->riskscore
        );
    }

    public function test_identity_requires_a_valid_userid(): void {
        $alert = new CrmAlert(
            key: 'inactive_user',
            displayname: 'Marie Dupont'
        );

        $this->assertFalse(
            $alert->has_user_identity()
        );
    }

    public function test_identity_requires_a_non_empty_name(): void {
        $alert = new CrmAlert(
            key: 'inactive_user',
            userid: 42,
            displayname: '   '
        );

        $this->assertFalse(
            $alert->has_user_identity()
        );
    }
}