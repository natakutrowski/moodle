<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromiseService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinContext;

final class commerce_797m61_customer_access_promise_test extends advanced_testcase {
    private function create_product(string $sku, string $type): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => $type,
            'status' => 'active',
            'name' => $sku,
            'description' => null,
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_promotion(int $courseid, string $key, int $startsat): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => $key,
            'name' => 'Promotion M6.1',
            'courseid' => $courseid,
            'status' => 'open',
            'published' => 1,
            'salesopensat' => $now - HOURSECS,
            'salesclosesat' => $now + DAYSECS,
            'startsat' => $startsat,
            'endsat' => null,
            'capacitytotal' => 10,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_progressive_course_exposes_promotion_and_first_unlock(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $productid = $this->create_product('M61-PROGRESSIVE', 'course_access');
        $promotionid = $this->create_promotion((int)$course->id, 'm61-progressive', $now + DAYSECS);

        $DB->insert_record('local_subs_commerce_ped_offer', (object)[
            'promotionid' => $promotionid,
            'productid' => $productid,
            'capacity' => 10,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ], '*', MUST_EXIST);
        $firstunlock = $now + DAYSECS + HOURSECS;
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promotionid,
            'itemtype' => 'course_section',
            'itemid' => $section->id,
            'position' => 1,
            'unlocksat' => $firstunlock,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $promise = CommerceCustomerAccessPromiseService::create($DB)->resolve(
            'M61-PROGRESSIVE',
            'course_access',
            false,
            null,
            $now
        );

        self::assertSame('promotion_progressive', $promise['kind']);
        self::assertTrue($promise['haspromotion']);
        self::assertSame($promotionid, $promise['promotionid']);
        self::assertSame('Promotion M6.1', $promise['promotionname']);
        self::assertSame($now + DAYSECS, $promise['startsat']);
        self::assertSame($firstunlock, $promise['firstunlocksat']);
        self::assertTrue($promise['progressive']);
        self::assertFalse($promise['preservesexistingaccess']);
    }

    public function test_owner_join_explicitly_preserves_existing_course_access(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product('M61-OWNER', 'course_access');
        $promotionid = $this->create_promotion((int)$course->id, 'm61-owner', $now + DAYSECS);

        $context = new CommercePedagogicalPromotionJoinContext(
            42,
            'M61-OWNER',
            $productid,
            'native_entitlement',
            $promotionid,
            'm61-owner',
            'Promotion M6.1',
            (int)$course->id,
            3
        );

        $promise = CommerceCustomerAccessPromiseService::create($DB)->resolve(
            'M61-OWNER',
            'course_access',
            true,
            $context,
            $now
        );

        self::assertSame('owner_promotion_join', $promise['kind']);
        self::assertTrue($promise['haspromotion']);
        self::assertSame($promotionid, $promise['promotionid']);
        self::assertTrue($promise['preservesexistingaccess']);
    }

    public function test_non_course_product_types_have_clear_non_pedagogical_contracts(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceCustomerAccessPromiseService::create($DB);

        self::assertSame(
            'immediate_digital',
            $service->resolve('M61-DIGITAL', 'digital', false, null)['kind']
        );
        self::assertSame(
            'bundle',
            $service->resolve('M61-BUNDLE', 'bundle', false, null)['kind']
        );
        self::assertSame(
            'immediate_course',
            $service->resolve('M61-COURSE', 'course_access', false, null)['kind']
        );
    }
}
