<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\service\SubscriptionLifecycleService;
use local_subscriptions\trial\TrialLegacyExpiryService;

/** @covers \local_subscriptions\trial\TrialLegacyExpiryService */
final class trial_legacy_expiry_798d4_test extends \advanced_testcase {
    public function test_date_b_expires_trial_and_revokes_trial_only_course_access(): void {
        global $DB;

        $this->resetAfterTest(true);
        $fixture = $this->make_trial_fixture(false);
        $now = time();
        set_config('trial_registration_close_at', (string)($now - 2 * HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now - HOURSECS), 'local_subscriptions');

        $result = TrialLegacyExpiryService::create()->expire_due($now);

        self::assertTrue($result['due']);
        self::assertSame(1, $result['subscriptions_expired']);
        self::assertSame(1, $result['campus_rows_expired']);
        self::assertSame(2, $result['trial_roles_removed']);
        self::assertSame(2, $result['enrolments_suspended']);
        self::assertSame(0, $result['courses_preserved']);

        $subscription = $DB->get_record('user_subscription', ['id' => $fixture['subscriptionid']], '*', MUST_EXIST);
        self::assertSame(Status::EXPIRED, $subscription->status);
        self::assertLessThanOrEqual($now - HOURSECS, (int)$subscription->end_date);

        foreach ($fixture['courseids'] as $courseid) {
            self::assertFalse($this->has_role((int)$fixture['user']->id, $courseid, 'trialstudent'));
            self::assertSame(ENROL_USER_SUSPENDED, $this->manual_enrolment_status((int)$fixture['user']->id, $courseid));
        }

        $campus = $DB->get_record('local_campus_trial', ['userid' => $fixture['user']->id], '*', MUST_EXIST);
        self::assertSame(2, (int)$campus->status);
        self::assertEmpty($DB->get_field('user', 'suspended', ['id' => $fixture['user']->id]));
    }

    public function test_paid_course_is_preserved_while_remaining_trial_course_is_revoked(): void {
        global $DB;

        $this->resetAfterTest(true);
        $fixture = $this->make_trial_fixture(true);
        $now = time();
        set_config('trial_registration_close_at', (string)($now - 2 * HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now - HOURSECS), 'local_subscriptions');

        $result = TrialLegacyExpiryService::create()->expire_due($now);

        self::assertSame(1, $result['courses_preserved']);
        self::assertSame(1, $result['enrolments_suspended']);
        self::assertFalse($this->has_role((int)$fixture['user']->id, $fixture['courseids'][0], 'trialstudent'));
        self::assertTrue($this->has_role((int)$fixture['user']->id, $fixture['courseids'][0], 'student'));
        self::assertSame(ENROL_USER_ACTIVE, $this->manual_enrolment_status(
            (int)$fixture['user']->id,
            $fixture['courseids'][0]
        ));

        self::assertFalse($this->has_role((int)$fixture['user']->id, $fixture['courseids'][1], 'trialstudent'));
        self::assertSame(ENROL_USER_SUSPENDED, $this->manual_enrolment_status(
            (int)$fixture['user']->id,
            $fixture['courseids'][1]
        ));
    }

    public function test_before_date_b_is_noop_and_second_date_b_run_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest(true);
        $fixture = $this->make_trial_fixture(false);
        $now = time();
        set_config('trial_registration_close_at', (string)($now - HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now + HOURSECS), 'local_subscriptions');

        $before = TrialLegacyExpiryService::create()->expire_due($now);
        self::assertFalse($before['due']);
        self::assertSame(Status::ACTIVE, $DB->get_field('user_subscription', 'status', ['id' => $fixture['subscriptionid']]));

        set_config('trial_legacy_expire_at', (string)($now - 1), 'local_subscriptions');
        $first = TrialLegacyExpiryService::create()->expire_due($now);
        $second = TrialLegacyExpiryService::create()->expire_due($now);

        self::assertSame(1, $first['subscriptions_expired']);
        self::assertSame(0, $second['subscriptions_expired']);
        self::assertSame(0, $second['campus_rows_expired']);
        self::assertSame(0, $second['trial_roles_removed']);
        self::assertSame(0, $second['enrolments_suspended']);
    }

    public function test_individual_trial_expiry_uses_same_safe_revocation(): void {
        global $DB;

        $this->resetAfterTest(true);
        $fixture = $this->make_trial_fixture(true);
        $now = time();

        $service = new SubscriptionLifecycleService();
        self::assertTrue($service->expire($fixture['subscriptionid'], $now));

        self::assertSame(Status::EXPIRED, $DB->get_field('user_subscription', 'status', ['id' => $fixture['subscriptionid']]));
        self::assertSame(ENROL_USER_ACTIVE, $this->manual_enrolment_status(
            (int)$fixture['user']->id,
            $fixture['courseids'][0]
        ));
        self::assertSame(ENROL_USER_SUSPENDED, $this->manual_enrolment_status(
            (int)$fixture['user']->id,
            $fixture['courseids'][1]
        ));
    }

    public function test_campus_only_residual_trial_role_is_removed_at_date_b(): void {
        global $DB;

        $this->resetAfterTest(true);
        $fixture = $this->make_trial_fixture(false);
        $DB->delete_records('user_subscription', ['id' => $fixture['subscriptionid']]);

        $now = time();
        set_config('trial_registration_close_at', (string)($now - 2 * HOURSECS), 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now - HOURSECS), 'local_subscriptions');

        $result = TrialLegacyExpiryService::create()->expire_due($now);
        self::assertSame(0, $result['subscriptions_expired']);
        self::assertSame(1, $result['campus_rows_expired']);
        self::assertSame(2, $result['trial_roles_removed']);
        self::assertSame(2, $result['enrolments_suspended']);
    }

    /** @return array{user:\stdClass,subscriptionid:int,planid:int,courseids:int[]} */
    private function make_trial_fixture(bool $paidfirstcourse): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/accesslib.php');

        $now = time();
        $user = $this->getDataGenerator()->create_user();
        $courses = [
            $this->getDataGenerator()->create_course(),
            $this->getDataGenerator()->create_course(),
        ];
        $courseids = array_map(static fn(\stdClass $course): int => (int)$course->id, $courses);

        $trialroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($trialroleid <= 0) {
            $trialroleid = create_role('Trial student D4', 'trialstudent', 'Trial role for D4 tests');
        }
        $studentroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], IGNORE_MISSING);
        if ($studentroleid <= 0) {
            $studentroleid = create_role('Student D4', 'student', 'Student role for D4 tests');
        }

        $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
            'name' => 'Trial D4 scope ' . $user->id,
            'course_ids' => implode(',', $courseids),
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial D4 ' . $user->id,
            'accessscopeid' => $scopeid,
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 1,
            'creation_date' => $now - DAYSECS,
            'last_update' => $now - DAYSECS,
        ]);
        foreach ($courseids as $index => $courseid) {
            $DB->insert_record('subscription_plan_entitlement', (object)[
                'planid' => $planid,
                'courseid' => $courseid,
                'accesslevel' => 'trial',
                'roleshortname' => 'trialstudent',
                'groupname' => null,
                'priority' => 100 + $index,
                'timecreated' => $now,
                'lastupdate' => $now,
            ]);
        }
        set_config('trial_plan_id', $planid, 'local_subscriptions');

        $subscriptionid = (int)$DB->insert_record('user_subscription', (object)[
            'userid' => (int)$user->id,
            'planid' => $planid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'transactionid' => 'trial-d4-' . $user->id,
            'payment_provider' => 'trial',
            'start_date' => $now - HOURSECS,
            'end_date' => $now + 10 * DAYSECS,
            'status' => Status::ACTIVE,
            'creation_date' => $now - HOURSECS,
            'last_update' => $now - HOURSECS,
        ]);

        subscription_manager::enrol_user_to_courses((int)$user->id, $planid, $now - HOURSECS, $now + 10 * DAYSECS);

        if ($paidfirstcourse) {
            $context = \context_course::instance($courseids[0]);
            role_assign($studentroleid, (int)$user->id, $context->id);
        }

        $DB->insert_record('local_campus_trial', (object)[
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'userid' => (int)$user->id,
            'timecreated' => $now - HOURSECS,
            'expiresat' => $now + 10 * DAYSECS,
            'status' => 1,
        ]);

        return [
            'user' => $user,
            'subscriptionid' => $subscriptionid,
            'planid' => $planid,
            'courseids' => $courseids,
        ];
    }

    private function has_role(int $userid, int $courseid, string $shortname): bool {
        global $DB;
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING);
        if ($roleid <= 0) {
            return false;
        }
        return user_has_role_assignment($userid, $roleid, \context_course::instance($courseid)->id);
    }

    private function manual_enrolment_status(int $userid, int $courseid): int {
        global $DB;
        $sql = "SELECT ue.status
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid
                   AND e.courseid = :courseid
                   AND e.enrol = :manual
              ORDER BY ue.id ASC";
        return (int)$DB->get_field_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'manual' => 'manual',
        ]);
    }
}
