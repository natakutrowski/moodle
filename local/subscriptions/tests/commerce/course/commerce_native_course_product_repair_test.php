<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\repair\CommerceNativeCourseProductRepairService;

/**
 * @covers \local_subscriptions\commerce\course\repair\CommerceNativeCourseProductRepairService
 */
final class commerce_native_course_product_repair_test
        extends \advanced_testcase {

    public function test_repair_creates_mapping_and_entitlement_idempotently(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $courseid = (int)$course->id;
        $now = time();

        $planid = (int)$DB->insert_record(
            'subscription_plan',
            (object)[
                'name' => 'Grammar repair ' . $now,
                'accessscopeid' => null,
                'duration_key' => 'lifetime',
                'is_active' => 0,
                'is_recurring' => 0,
                'is_trial' => 0,
                'creation_date' => $now,
                'last_update' => $now,
                'expiry_reminder_enabled' => 1,
            ]
        );

        $legacyentitlementid = (int)$DB->insert_record(
            'subscription_plan_entitlement',
            (object)[
                'planid' => $planid,
                'courseid' => $courseid,
                'accesslevel' => 'grammar',
                'roleshortname' => 'grammarstudent',
                'groupname' => '',
                'priority' => 50,
                'timecreated' => $now,
                'lastupdate' => $now,
            ]
        );

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
                'sku' => 'COURSE_ACCESS.TEST_GRAMMAR',
                'type' => 'course_access',
                'status' => 'active',
                'name' => 'Test Grammar',
                'description' => '',
                'metadatajson' => null,
                'availablefrom' => null,
                'availableuntil' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $service = new CommerceNativeCourseProductRepairService($DB);

        $dryrun = $service->inspect(
            'COURSE_ACCESS.TEST_GRAMMAR',
            $planid,
            $courseid,
            'grammar',
            'grammarstudent'
        );

        $this->assertTrue($dryrun['ready']);
        $this->assertFalse($dryrun['complete']);
        $this->assertCount(2, $dryrun['actions']);

        $applied = $service->apply(
            'COURSE_ACCESS.TEST_GRAMMAR',
            $planid,
            $courseid,
            'grammar',
            'grammarstudent'
        );

        $this->assertTrue($applied['complete']);
        $this->assertSame([], $applied['errors']);

        $mapping = $DB->get_record(
            'local_subs_commerce_prod_map',
            [
                'legacytable' => 'subscription_plan',
                'legacyid' => $planid,
            ],
            '*',
            MUST_EXIST
        );
        $this->assertSame($productid, (int)$mapping->productid);

        $entitlement = $DB->get_record(
            'local_subs_commerce_prod_ent',
            [
                'productid' => $productid,
                'type' => 'course_access',
                'resourcekey' => 'course:' . $courseid . ':grammar',
            ],
            '*',
            MUST_EXIST
        );

        $configuration = json_decode(
            (string)$entitlement->configurationjson,
            true
        );

        $this->assertSame($courseid, (int)$configuration['courseid']);
        $this->assertSame('grammar', $configuration['accesslevel']);
        $this->assertSame(
            'grammarstudent',
            $configuration['roleshortname']
        );
        $this->assertSame($planid, (int)$configuration['sourceplanid']);
        $this->assertSame(
            $legacyentitlementid,
            (int)$configuration['legacyid']
        );
        $this->assertSame(50, (int)$entitlement->sortorder);

        $second = $service->apply(
            'COURSE_ACCESS.TEST_GRAMMAR',
            $planid,
            $courseid,
            'grammar',
            'grammarstudent'
        );

        $this->assertTrue($second['complete']);
        $this->assertCount(
            1,
            $DB->get_records(
                'local_subs_commerce_prod_map',
                [
                    'legacytable' => 'subscription_plan',
                    'legacyid' => $planid,
                ]
            )
        );
        $this->assertCount(
            1,
            $DB->get_records(
                'local_subs_commerce_prod_ent',
                [
                    'productid' => $productid,
                    'resourcekey' =>
                        'course:' . $courseid . ':grammar',
                ]
            )
        );

        $plan = $DB->get_record(
            'subscription_plan',
            ['id' => $planid],
            '*',
            MUST_EXIST
        );
        $this->assertSame(0, (int)$plan->is_active);
    }
}
