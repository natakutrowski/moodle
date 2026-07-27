<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot;

/** @coversDefaultClass \,local_subscriptions\commerce\domain\value\CommerceCustomerSnapshot */
final class commerce_customer_snapshot_test extends advanced_testcase {

    public function test_registered_customer_is_normalised(): void {
        $snapshot = new CommerceCustomerSnapshot(
            96,
            ' nata@example.com ',
            ' Nata ',
            ' Kutrowski ',
            'fr',
            'FR',
            ['source' => 'checkout']
        );

        $this->assertSame(96, $snapshot->get_user_id());
        $this->assertSame('nata@example.com', $snapshot->get_email());
        $this->assertSame('Nata Kutrowski', $snapshot->get_fullname());
        $this->assertSame('FR', $snapshot->get_country());
        $this->assertSame('fr', $snapshot->get_language());
        $this->assertTrue($snapshot->is_registered_user());
    }

    public function test_guest_customer_can_be_identified_by_email(): void {
        $snapshot = new CommerceCustomerSnapshot(
            null,
            'guest@example.com'
        );

        $this->assertTrue($snapshot->is_guest());
    }

    public function test_snapshot_requires_a_customer_identity(): void {
        $this->expectException(\coding_exception::class);
        new CommerceCustomerSnapshot(null, null);
    }

    public function test_invalid_country_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommerceCustomerSnapshot(4, null, null, null, 'France');
    }
}
