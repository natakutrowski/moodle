<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\trial\CommerceTrialConversionBridge;

/** @covers \local_subscriptions\commerce\trial\CommerceTrialConversionBridge */
final class commerce_trial_conversion_bridge_test extends \advanced_testcase {
    public function test_active_trial_resolves_configured_native_product(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $courseid = (int)$course->id;
        $now = time();

        $scopeid = (int)$DB->insert_record(
            'subscription_access_scope',
            (object)[
                'name' => 'Trial scope',
                'course_ids' => (string)$courseid,
                'creation_date' => $now,
                'last_update' => $now,
            ]
        );

        $trialplanid = (int)$DB->insert_record('subscription_plan', (object)[
            'name' => 'Trial',
            'duration_key' => '1month',
            'is_active' => 1,
            'is_trial' => 1,
            'accessscopeid' => $scopeid,
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        set_config('trial_plan_id', $trialplanid, 'local_subscriptions');
        set_config('trial_discount_percent', 20, 'local_subscriptions');
        set_config('trial_discount_hours', 72, 'local_subscriptions');
        set_config('trial_conversion_product_sku', 'COURSE.A1.FULL', 'local_subscriptions');

        $DB->insert_record('user_subscription', (object)[
            'userid' => $user->id,
            'planid' => $trialplanid,
            'pricepaid' => 0,
            'currency' => 'EUR',
            'transactionid' => 'trial-test',
            'payment_provider' => 'trial',
            'start_date' => $now - HOURSECS,
            'end_date' => $now + DAYSECS,
            'status' => 'ACTIVE',
            'creation_date' => $now,
        ]);

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
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
            ]
        );

        $DB->insert_record(
            'local_subs_commerce_prod_ent',
            (object)[
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
            ]
        );

        $offer = (new CommerceTrialConversionBridge($DB))
            ->resolve_for_user(
                (int)$user->id,
                'EUR',
                'COURSE.A1.FULL'
            );

        $this->assertNotNull($offer);
        $this->assertSame(20, $offer->get_discount_percent());
        $this->assertSame('COURSE.A1.FULL', $offer->get_product_sku());
        $this->assertStringContainsString(
            '/local/subscriptions/storefront_product.php',
            $offer->get_url()->out(false)
        );
        $this->assertStringNotContainsString(
            'trialconversion=1',
            $offer->get_url()->out(false)
        );
    }

    public function test_non_trial_user_has_no_conversion_offer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $this->assertNull(
            (new CommerceTrialConversionBridge($DB))
                ->resolve_for_user((int)$user->id, 'EUR')
        );
    }
}
