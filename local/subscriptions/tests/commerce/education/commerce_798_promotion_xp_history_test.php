<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpHistoryService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;

/** Read-only privacy and reconciliation tests for the learner promotion XP history. */
final class commerce_798_promotion_xp_history_test extends advanced_testcase {
    /** @return array{0:int,1:int,2:int} Promotion, course and user IDs. */
    private function participant(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $now = time();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'HIST-' . strtoupper(random_string(10)),
            'type' => 'course_access', 'status' => 'active',
            'name' => 'History test product', 'description' => null, 'metadatajson' => null,
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'hist-' . random_string(10),
            'name' => 'History test', 'courseid' => (int)$course->id,
            'status' => 'started', 'published' => 1,
            'salesopensat' => null, 'salesclosesat' => null, 'startsat' => null, 'endsat' => null,
            'capacitytotal' => null, 'createdby' => null, 'modifiedby' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => (int)$course->id,
            'userid' => (int)$user->id, 'productid' => $productid,
            'purchasereference' => 'test-' . random_string(12), 'state' => 'active',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return [$promotionid, (int)$course->id, (int)$user->id];
    }

    public function test_only_active_member_can_read_history(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$promotionid, , $userid] = $this->participant();
        $stranger = $this->getDataGenerator()->create_user();
        $service = CommercePedagogicalXpHistoryService::create($DB);
        self::assertNull($service->get_for_user($promotionid, (int)$stranger->id));
        self::assertNull($service->get_for_user(0, $userid));
        self::assertNull($service->get_for_user($promotionid, 0));
        self::assertNotNull($service->get_for_user($promotionid, $userid));
        $DB->set_field('local_subs_commerce_ped_join', 'state', 'revoked', [
            'promotionid' => $promotionid, 'userid' => $userid,
        ]);
        self::assertNull($service->get_for_user($promotionid, $userid));
    }

    public function test_exact_own_ledger_and_pagination_without_other_users(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$promotionid, , $userid] = $this->participant();
        $other = $this->getDataGenerator()->create_user();
        $xp = CommercePedagogicalXpService::create($DB);
        $now = time();
        $xp->record_once($promotionid, $userid, 'block_xp', 'points_increased', 'one', 12, $now - 10, $now);
        $xp->record_once($promotionid, $userid, 'block_xp', 'points_increased', 'two', 8, $now, $now);
        // Source rows from a different learner must never be returned (even if present).
        $DB->insert_record('local_subs_commerce_ped_xp', (object)[
            'promotionid' => $promotionid, 'courseid' => $DB->get_field('local_subs_commerce_ped_promo',
                'courseid', ['id' => $promotionid]),
            'userid' => (int)$other->id, 'sourcecomponent' => 'block_xp',
            'sourcetype' => 'points_increased', 'sourcekey' => 'other',
            'sourcehash' => hash('sha256', 'block_xp' . "\n" . 'points_increased' . "\n" . 'other'),
            'points' => 99, 'timeearned' => $now, 'timecreated' => $now,
        ]);
        $summary = CommercePedagogicalXpHistoryService::create($DB)->get_for_user($promotionid, $userid);
        self::assertSame(20, $summary['totalpoints']);
        self::assertSame(2, $summary['rewardcount']);
        self::assertCount(2, $summary['rows']);
        self::assertSame(8, $summary['rows'][0]['points']);
        self::assertSame(12, $summary['rows'][1]['points']);
        self::assertSame(0, $summary['page']);
        self::assertTrue($summary['rows'][0]['isactive']);
        // An out-of-range page is clamped to the last available page.
        self::assertSame(0, CommercePedagogicalXpHistoryService::create($DB)
            ->get_for_user($promotionid, $userid, 123)['page']);
    }

    public function test_crm_history_requires_view_permission(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$promotionid, , $userid] = $this->participant();
        $this->setUser($userid);
        $this->expectException(\required_capability_exception::class);
        CommercePedagogicalXpHistoryService::create($DB)->get_for_crm_promotion($promotionid);
    }

    public function test_crm_history_can_audit_revoked_member_without_changing_ledger(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$promotionid, , $userid] = $this->participant();
        $now = time();
        $xp = CommercePedagogicalXpService::create($DB);
        $xp->record_once($promotionid, $userid, 'block_xp', 'points_increased', 'first',
            12, $now - 40, $now - 40);
        $adjustmentid = (int)$DB->insert_record('local_subs_commerce_ped_xpadj', (object)[
            'promotionid' => $promotionid, 'targettype' => 'user',
            'userid' => $userid, 'groupid' => null, 'points' => 9,
            'reason' => 'For CRM support review only', 'actorid' => $userid,
            'requestkey' => random_string(32), 'timecreated' => $now - 20,
            'cancelledat' => $now - 5, 'cancelledby' => $userid,
        ]);
        $xp->record_once($promotionid, $userid, 'local_subscriptions', 'manual_adjustment',
            'adjustment:' . $adjustmentid, 9, $now - 20, $now - 20);
        $before = $DB->count_records('local_subs_commerce_ped_xp', ['promotionid' => $promotionid]);
        $DB->set_field('local_subs_commerce_ped_join', 'state', 'revoked',
            ['promotionid' => $promotionid, 'userid' => $userid]);
        self::assertNull(CommercePedagogicalXpHistoryService::create($DB)
            ->get_for_user($promotionid, $userid));

        $this->setAdminUser();
        $service = CommercePedagogicalXpHistoryService::create($DB);
        $overview = $service->get_for_crm_promotion($promotionid);
        self::assertSame(12, $overview['totalpoints']);
        self::assertSame(2, $overview['rewardcount']);
        self::assertCount(2, $overview['rows']);
        self::assertTrue($overview['rows'][0]['iscancelled']);
        self::assertSame($userid, $overview['rows'][0]['userid']);
        $individual = $service->get_for_crm_user($promotionid, $userid);
        self::assertSame(12, $individual['totalpoints']);
        self::assertSame('For CRM support review only', $individual['rows'][0]['crmreason']);
        self::assertNull($service->get_for_crm_user($promotionid, 0));
        self::assertNull($service->get_for_crm_promotion(-1));
        self::assertSame($before, $DB->count_records('local_subs_commerce_ped_xp',
            ['promotionid' => $promotionid]));
    }

    public function test_cancelled_individual_bonus_is_visible_but_subtracted(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$promotionid, , $userid] = $this->participant();
        $now = time();
        $adjustmentid = (int)$DB->insert_record('local_subs_commerce_ped_xpadj', (object)[
            'promotionid' => $promotionid, 'targettype' => 'user',
            'userid' => $userid, 'groupid' => null, 'points' => 23,
            'reason' => 'private CRM note - never reveal',
            'actorid' => $userid, 'requestkey' => random_string(32),
            'timecreated' => $now - 20, 'cancelledat' => $now, 'cancelledby' => $userid,
        ]);
        CommercePedagogicalXpService::create($DB)->record_once($promotionid, $userid,
            'local_subscriptions', 'manual_adjustment', 'adjustment:' . $adjustmentid,
            23, $now - 20, $now - 20);
        $summary = CommercePedagogicalXpHistoryService::create($DB)->get_for_user($promotionid, $userid);
        self::assertSame(0, $summary['totalpoints']);
        self::assertSame(1, $summary['rewardcount']);
        self::assertTrue($summary['rows'][0]['iscancelled']);
        self::assertFalse($summary['rows'][0]['isactive']);
        self::assertStringNotContainsString('private CRM', json_encode($summary));
    }
}
