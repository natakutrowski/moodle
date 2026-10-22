<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialConversionBridge;
use local_subscriptions\constants\Status;
use local_subscriptions\trial\TrialLegacyConversionEligibilityService;

/** @covers \local_subscriptions\trial\TrialLegacyConversionEligibilityService */
final class trial_conversion_window_798d3_test extends \advanced_testcase {
    public function test_expired_preclosure_trial_keeps_conversion_until_date_b(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - HOURSECS;
        $expireat = $now + DAYSECS;
        [$user, $planid] = $this->make_trial(Status::EXPIRED, $closeat - DAYSECS, $closeat - HOURSECS);

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)$expireat, 'local_subscriptions');

        $service = new TrialLegacyConversionEligibilityService($DB);
        self::assertTrue($service->is_eligible((int)$user->id, $now));
        self::assertSame($expireat, $service->deadline((int)$user->id, $now));
    }

    public function test_replaced_trial_is_not_resurrected_by_transition_grace(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - HOURSECS;
        [$user, $planid] = $this->make_trial(Status::REPLACED, $closeat - DAYSECS, $closeat - HOURSECS);

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now + DAYSECS), 'local_subscriptions');

        $service = new TrialLegacyConversionEligibilityService($DB);
        self::assertFalse($service->is_eligible((int)$user->id, $now));
        self::assertSame(0, $service->deadline((int)$user->id, $now));
    }

    public function test_trial_created_after_date_a_does_not_enter_legacy_cohort(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - DAYSECS;
        [$user, $planid] = $this->make_trial(Status::ACTIVE, $now - HOURSECS, $now + DAYSECS);

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)($now + DAYSECS), 'local_subscriptions');

        $service = new TrialLegacyConversionEligibilityService($DB);
        self::assertFalse($service->is_eligible((int)$user->id, $now));
    }

    public function test_date_b_closes_conversion_at_exact_boundary(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - DAYSECS;
        $expireat = $now + HOURSECS;
        [$user, $planid] = $this->make_trial(Status::EXPIRED, $closeat - DAYSECS, $closeat - HOURSECS);

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)$expireat, 'local_subscriptions');

        $service = new TrialLegacyConversionEligibilityService($DB);
        self::assertTrue($service->is_eligible((int)$user->id, $expireat - 1));
        self::assertFalse($service->is_eligible((int)$user->id, $expireat));
        self::assertSame(0, $service->deadline((int)$user->id, $expireat));
    }


    public function test_legacy_campus_only_trial_is_not_stranded_during_grace(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - HOURSECS;
        $expireat = $now + DAYSECS;
        $user = $this->getDataGenerator()->create_user();

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)$expireat, 'local_subscriptions');

        $DB->insert_record('local_campus_trial', (object)[
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'userid' => (int)$user->id,
            'timecreated' => $closeat - DAYSECS,
            'expiresat' => $closeat - HOURSECS,
            'status' => 2,
        ]);

        $service = new TrialLegacyConversionEligibilityService($DB);
        self::assertTrue($service->is_eligible((int)$user->id, $now));
        self::assertSame($expireat, $service->deadline((int)$user->id, $now));
    }


    public function test_native_bridge_refuses_conversion_after_date_b(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - DAYSECS;
        [$user, $planid] = $this->make_trial(Status::EXPIRED, $closeat - DAYSECS, $closeat - HOURSECS);

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)$now, 'local_subscriptions');
        set_config('trial_discount_percent', 20, 'local_subscriptions');

        self::assertNull(
            (new CommerceTrialConversionBridge($DB))->resolve_for_user((int)$user->id, 'EUR')
        );
    }

    public function test_native_bridge_resolves_expired_trial_during_transition_grace(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $closeat = $now - HOURSECS;
        $expireat = $now + DAYSECS;
        [$user, $planid, $courseid] = $this->make_trial(
            Status::EXPIRED,
            $closeat - DAYSECS,
            $closeat - HOURSECS,
            true
        );

        set_config('trial_registration_close_at', (string)$closeat, 'local_subscriptions');
        set_config('trial_legacy_expire_at', (string)$expireat, 'local_subscriptions');
        set_config('trial_discount_percent', 20, 'local_subscriptions');
        set_config('trial_discount_hours', 72, 'local_subscriptions');

        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'COURSE.A1.FULL',
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'A1 Full',
            'description' => '',
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_ent', (object)[
            'productid' => $productid,
            'type' => 'course_access',
            'resourcekey' => 'course:' . $courseid . ':full',
            'durationseconds' => null,
            'quantity' => 1,
            'configurationjson' => json_encode([
                'courseid' => $courseid,
                'accesslevel' => 'full',
                'roleshortname' => 'student',
            ]),
            'sortorder' => 100,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $offer = (new CommerceTrialConversionBridge($DB))->resolve_for_user(
            (int)$user->id,
            'EUR',
            'COURSE.A1.FULL'
        );

        self::assertNotNull($offer);
        self::assertSame($expireat, $offer->get_expires_at());
        self::assertSame('COURSE.A1.FULL', $offer->get_product_sku());
        self::assertSame(20, $offer->get_discount_percent());
    }

    /**
     * @return array{0:\stdClass,1:int,2?:int}
     */
    private function make_trial(
        string $status,
        int $createdat,
        int $enddate,
        bool $withcourse = false
    ): array {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $courseid = 0;
        $scopeid = 0;

        if ($withcourse) {
            $course = $this->getDataGenerator()->create_course();
            $courseid = (int)$course->id;
            $scopeid = (int)$DB->insert_record('subscription_access_scope', (object)[
                'name' => 'Trial scope D3',
                'course_ids' => (string)$courseid,
                'creation_date' => $now,
                'last_update' => $now,
            ]);
        }

        $planid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial D3',
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 1,
            'accessscopeid' => $scopeid > 0 ? $scopeid : null,
            'creation_date' => $createdat,
            'last_update' => $createdat,
        ]);
        set_config('trial_plan_id', $planid, 'local_subscriptions');

        $DB->insert_record('user_subscription', (object)[
            'userid' => (int)$user->id,
            'planid' => $planid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'transactionid' => 'trial-d3-' . $user->id,
            'payment_provider' => 'trial',
            'start_date' => $createdat,
            'end_date' => $enddate,
            'status' => $status,
            'creation_date' => $createdat,
            'last_update' => $createdat,
        ]);

        return $withcourse
            ? [$user, $planid, $courseid]
            : [$user, $planid];
    }
}
