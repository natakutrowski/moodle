<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

/** Tests Legacy course access resolution for Trial purchases. */
final class commerce_legacy_trial_order_access_resolver_test extends \advanced_testcase {
    public function test_trial_plan_is_available_even_without_trial_status_value(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Cours Trial',
        ]);

        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Trial scope ' . uniqid('', true),
            'course_ids' => (string)$course->id,
            'creation_date' => time(),
            'last_update' => time(),
        ]);

        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial plan ' . uniqid('', true),
            'accessscopeid' => $scopeid,
            'duration_key' => '1week',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 1,
            'expiry_reminder_enabled' => 0,
        ]);

        $userid = $this->getDataGenerator()->create_user()->id;
        $subscriptionid = $DB->insert_record('user_subscription', (object)[
            'userid' => $userid,
            'planid' => $planid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'start_date' => time() - HOURSECS,
            'end_date' => time() + DAYSECS,
            // Deliberately not "trial": that status does not exist in the Legacy model.
            'status' => 'inactive',
            'creation_date' => time(),
        ]);

        $resolver = new CommerceLegacyOrderAccessResolver($DB);
        $actions = $resolver->resolve('subscription', $subscriptionid);

        $this->assertCount(1, $actions);
        $this->assertSame('course_access', $actions[0]['type']);
        $this->assertTrue($actions[0]['available']);
        $this->assertSame('Cours Trial', $actions[0]['resourcelabel']);
    }

    public function test_expired_trial_is_not_available(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Expired trial scope ' . uniqid('', true),
            'course_ids' => (string)$course->id,
            'creation_date' => time(),
            'last_update' => time(),
        ]);
        $planid = $DB->insert_record('subscription_plan', (object)[
            'name' => 'Expired trial plan ' . uniqid('', true),
            'accessscopeid' => $scopeid,
            'duration_key' => '1week',
            'is_active' => 1,
            'creation_date' => time(),
            'last_update' => time(),
            'is_recurring' => 0,
            'is_trial' => 1,
            'expiry_reminder_enabled' => 0,
        ]);
        $userid = $this->getDataGenerator()->create_user()->id;
        $subscriptionid = $DB->insert_record('user_subscription', (object)[
            'userid' => $userid,
            'planid' => $planid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'start_date' => time() - (2 * DAYSECS),
            'end_date' => time() - DAYSECS,
            'status' => 'inactive',
            'creation_date' => time(),
        ]);

        $resolver = new CommerceLegacyOrderAccessResolver($DB);
        $actions = $resolver->resolve('subscription', $subscriptionid);

        $this->assertCount(1, $actions);
        $this->assertFalse($actions[0]['available']);
    }
}
