<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalGroupLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;

final class commerce_797m83e_refund_revoke_promotion_join_preservation_test extends advanced_testcase {
    public function test_refund_revoke_join_removes_promotion_participation_but_preserves_owner_access_and_xp(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/group/lib.php');

        $now = time();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id, 'student', 'manual');

        $sku = 'M83E-' . strtoupper(random_string(10));
        $productid = $this->product($sku, $now);
        $promotionid = $this->promotion((int)$course->id, $now);
        $groupid = $this->pedagogical_group($promotionid, (int)$course->id, $productid, $now);
        $moodlegroupid = (int)$DB->get_field(
            'local_subs_commerce_ped_group',
            'moodlegroupid',
            ['id' => $groupid],
            MUST_EXIST
        );

        $ownerpurchase = 'cmp_m83e_owner_' . random_string(8);
        $joinpurchase = 'cmp_m83e_join_' . random_string(8);
        $this->purchase((int)$user->id, $ownerpurchase, $now);
        $this->purchase((int)$user->id, $joinpurchase, $now + 1);

        $ownergrant = $this->grant(
            $ownerpurchase,
            (int)$user->id,
            $sku,
            'course_access',
            'course:' . $course->id . ':full',
            $now,
            ['courseid' => (int)$course->id]
        );
        $joingrant = $this->grant(
            $joinpurchase,
            (int)$user->id,
            $sku,
            'pedagogical_promotion_join',
            'promotion:' . $promotionid . ':course:' . $course->id . ':product:' . $productid,
            $now + 1,
            [
                'promotion_join_promotion_id' => $promotionid,
                'promotion_join_course_id' => (int)$course->id,
                'promotion_join_user_id' => (int)$user->id,
            ]
        );

        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            $promotionid,
            (int)$course->id,
            (int)$user->id,
            $sku,
            $joinpurchase,
            $now + 1
        );

        $DB->insert_record('local_subs_commerce_ped_gmem', (object)[
            'groupid' => $groupid,
            'userid' => (int)$user->id,
            'active' => 1,
            'createdby' => null,
            'timecreated' => $now + 1,
            'timemodified' => $now + 1,
        ]);
        self::assertTrue(groups_add_member($moodlegroupid, (int)$user->id));

        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null,
                (int)$course->id,
                (int)$user->id,
                $promotionid,
                CommerceStudentAccessProfile::LIFETIME_FULL,
                null,
                null,
                $now,
                $now + 1
            )
        );

        CommercePedagogicalXpService::create($DB)->record_once(
            $promotionid,
            (int)$user->id,
            'block_xp',
            'points_increased',
            'm83e:promotion-xp',
            37,
            $now + 2,
            $now + 2
        );
        $DB->insert_record('block_xp', (object)[
            'courseid' => SITEID,
            'userid' => (int)$user->id,
            'xp' => 123,
            'lvl' => 2,
        ]);

        self::assertSame(37, CommercePedagogicalXpRepository::create($DB)->user_points(
            $promotionid,
            (int)$user->id
        ));
        self::assertSame(123, (int)$DB->get_field('block_xp', 'xp', [
            'courseid' => SITEID,
            'userid' => (int)$user->id,
        ], MUST_EXIST));
        self::assertTrue(groups_is_member($moodlegroupid, (int)$user->id));

        $result = CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $joinpurchase,
            (int)$user->id,
            'M8.3-E refund + revoke',
            $now + 10,
            CommercePedagogicalParticipationRepository::REFUNDED
        );

        self::assertSame(1, $result['revoked']);
        self::assertSame(0, $result['course']);
        self::assertSame(0, $result['digital']);
        self::assertSame(1, $result['promotionjoin']);

        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $ownergrant,
        ], MUST_EXIST));
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $joingrant,
        ], MUST_EXIST));
        self::assertSame('refunded', $DB->get_field('local_subs_commerce_ped_join', 'state', [
            'purchasereference' => $joinpurchase,
        ], MUST_EXIST));
        self::assertSame(0, (int)$DB->get_field('local_subs_commerce_ped_gmem', 'active', [
            'groupid' => $groupid,
            'userid' => (int)$user->id,
        ], MUST_EXIST));
        self::assertFalse(groups_is_member($moodlegroupid, (int)$user->id));

        $access = CommerceStudentCourseAccessRepository::create($DB)->find(
            (int)$course->id,
            (int)$user->id
        );
        self::assertNotNull($access);
        self::assertNull($access->get_promotion_id());
        self::assertSame(CommerceStudentAccessProfile::LIFETIME_FULL, $access->get_profile());

        self::assertTrue($this->has_course_enrolment((int)$user->id, (int)$course->id));
        self::assertSame(37, CommercePedagogicalXpRepository::create($DB)->user_points(
            $promotionid,
            (int)$user->id
        ));
        self::assertSame(123, (int)$DB->get_field('block_xp', 'xp', [
            'courseid' => SITEID,
            'userid' => (int)$user->id,
        ], MUST_EXIST));

        $ranking = CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotionid);
        self::assertCount(1, $ranking);
        self::assertSame(0, $ranking[0]->get_member_count());
        self::assertSame(0, $ranking[0]->get_points());

        $second = CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $joinpurchase,
            (int)$user->id,
            'M8.3-E replay',
            $now + 20,
            CommercePedagogicalParticipationRepository::REFUNDED
        );
        self::assertSame([
            'revoked' => 0,
            'course' => 0,
            'digital' => 0,
            'promotionjoin' => 0,
            'preservedcourse' => 0,
        ], $second);
        self::assertSame(37, CommercePedagogicalXpRepository::create($DB)->user_points(
            $promotionid,
            (int)$user->id
        ));
        self::assertSame(123, (int)$DB->get_field('block_xp', 'xp', [
            'courseid' => SITEID,
            'userid' => (int)$user->id,
        ], MUST_EXIST));
    }

    private function product(string $sku, int $now): int {
        global $DB;
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku,
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'M8.3-E test product',
            'description' => null,
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function promotion(int $courseid, int $now): int {
        global $DB;
        return (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'm83e-' . strtolower(random_string(10)),
            'name' => 'M8.3-E promotion',
            'courseid' => $courseid,
            'status' => 'open',
            'published' => 1,
            'salesopensat' => null,
            'salesclosesat' => null,
            'startsat' => $now,
            'endsat' => null,
            'capacitytotal' => 6,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function pedagogical_group(int $promotionid, int $courseid, int $productid, int $now): int {
        global $DB;
        $moodlegroup = $this->getDataGenerator()->create_group([
            'courseid' => $courseid,
            'name' => 'M8.3-E Moodle group',
        ]);
        return (int)$DB->insert_record('local_subs_commerce_ped_group', (object)[
            'promotionid' => $promotionid,
            'moodlegroupid' => (int)$moodlegroup->id,
            'productid' => $productid,
            'displayname' => 'M8.3-E Group',
            'position' => 0,
            'tutorid' => null,
            'tutorname' => null,
            'supportlang' => null,
            'telegramref' => null,
            'levelupxp' => null,
            'active' => 1,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function purchase(int $userid, string $reference, int $now): void {
        global $DB;
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => $reference,
            'type' => 'native',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => 'm83e-' . $userid . '@example.invalid',
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 100,
            'discountminor' => 0,
            'totalminor' => 100,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function grant(
        string $purchase,
        int $userid,
        string $productsku,
        string $type,
        string $resourcekey,
        int $now,
        array $configuration
    ): string {
        global $DB;
        $reference = 'ent-' . substr(hash('sha256', $purchase . '|' . $type), 0, 32);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $reference,
            'idempotencykey' => 'idem-' . substr(hash('sha256', $reference), 0, 40),
            'purchasereference' => $purchase,
            'itemreference' => $productsku,
            'productsku' => $productsku,
            'type' => $type,
            'resourcekey' => $resourcekey,
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'm83e-' . $userid . '@example.invalid',
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'configurationjson' => json_encode($configuration),
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $reference;
    }

    private function has_course_enrolment(int $userid, int $courseid): bool {
        global $DB;
        return $DB->record_exists_sql(
            'SELECT 1
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :userid
                AND e.courseid = :courseid',
            ['userid' => $userid, 'courseid' => $courseid]
        );
    }
}
