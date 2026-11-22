<?php
/**
 * CampusFR Avent C2.2c.2 — Native Commerce cycle certification, isolated PHPUnit DB only.
 * All courses, users, products, purchases and grants are PHPUnit fixtures; never real DEV data.
 */
declare(strict_types=1);
namespace local_campusreader;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\grant\CommerceManualProductGrantService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantPersister;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\persistence\CommercePersistentNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\persistence\MoodleCommerceNativeFulfillmentPersistenceRepository;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseEnrolmentService;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** @coversNothing */
final class commerce_cycle_test extends \advanced_testcase {
    private const PRODUCT_PREFIX = 'QA.C22C2.';

    public function test_inactive_qa_product_cannot_issue_manual_native_grant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $productid = $this->qa_product((int)$course->id, 'INACTIVE', 'inactive');
        $this->expectException(\moodle_exception::class);
        (new CommerceManualProductGrantService($DB))->grant((int)$user->id, $productid, (int)get_admin()->id, 'QA', time());
    }

    public function test_manual_native_lifetime_grant_is_idempotent_without_fabricated_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $productid = $this->qa_product((int)$course->id, 'MANUAL');
        $service = new CommerceManualProductGrantService($DB);
        $now = time() - 60;
        $first = $service->grant((int)$user->id, $productid, (int)get_admin()->id, 'QA lifetime', $now);
        self::assertSame(1, $first['created']);
        self::assertCount(1, $first['results']);
        $second = $service->grant((int)$user->id, $productid, (int)get_admin()->id, 'QA lifetime', $now + 10);
        self::assertSame(0, $second['created']);
        self::assertSame(1, $second['identical']);
        $grants = array_values($DB->get_records('local_subs_commerce_grant', ['beneficiaryuserid' => $user->id]));
        self::assertCount(1, $grants);
        self::assertSame('active', $grants[0]->status);
        self::assertNull($grants[0]->validuntil);
        self::assertSame('course:' . $course->id . ':full', $grants[0]->resourcekey);
        self::assertSame('crm_manual_grant', json_decode((string)$grants[0]->metadatajson, true)['source']);
        self::assertFalse($DB->record_exists(CommercePersistenceSchema::TABLE_PURCHASE, [
            'reference' => $grants[0]->purchasereference,
        ]));
        self::assertSame(1, $DB->count_records('local_subs_commerce_ful_attempt', [
            'grantreference' => $grants[0]->grantreference,
        ]));
        self::assertTrue(is_enrolled(\context_course::instance((int)$course->id), (int)$user->id, '', true));
        self::assertSame(1, $this->enrol_count((int)$course->id, (int)$user->id));
    }

    public function test_native_purchase_grant_and_explicit_revoke_remove_commerce_created_enrolment(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $purchase = $this->qa_purchase((int)$user->id, (string)$user->email);
        $reference = $this->fulfill_purchase_course_grant($purchase, $user, (int)$course->id);
        self::assertTrue(is_enrolled(\context_course::instance((int)$course->id), (int)$user->id, '', true));
        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $reference]));
        $service = CommercePurchaseRightsRevocationService::create($DB);
        $result = $service->revoke($purchase, (int)get_admin()->id, 'C22c2 PHPUnit QA');
        self::assertSame(1, $result['revoked']);
        self::assertSame(1, $result['course']);
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $reference]));
        self::assertSame(0, $this->enrol_count((int)$course->id, (int)$user->id));
        $again = $service->revoke($purchase, (int)get_admin()->id, 'C22c2 replay');
        self::assertSame(0, $again['revoked']);
    }

    public function test_purchase_revocation_preserves_independent_active_manual_native_grant(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $productid = $this->qa_product((int)$course->id, 'OVERLAP');
        (new CommerceManualProductGrantService($DB))->grant((int)$user->id, $productid, (int)get_admin()->id, 'QA overlap');
        $purchase = $this->qa_purchase((int)$user->id, (string)$user->email);
        $this->fulfill_purchase_course_grant($purchase, $user, (int)$course->id);
        $result = CommercePurchaseRightsRevocationService::create($DB)->revoke($purchase, (int)get_admin()->id, 'QA overlap');
        self::assertSame(1, $result['revoked']);
        self::assertSame(1, $result['preservedcourse']);
        self::assertTrue(is_enrolled(\context_course::instance((int)$course->id), (int)$user->id, '', true));
        self::assertSame(1, $this->enrol_count((int)$course->id, (int)$user->id));
    }

    public function test_purchase_revocation_preserves_preexisting_manual_moodle_enrolment(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $existing = (new MoodleCourseEnrolmentService())->apply(
            (int)$user->id, (int)$course->id, 'student', time() - 120, null, false
        );
        self::assertSame('created', $existing['status']);
        $purchase = $this->qa_purchase((int)$user->id, (string)$user->email);
        $this->fulfill_purchase_course_grant($purchase, $user, (int)$course->id);
        $result = CommercePurchaseRightsRevocationService::create($DB)->revoke($purchase, (int)get_admin()->id, 'QA prior enrol');
        self::assertSame(1, $result['preservedcourse']);
        self::assertTrue(is_enrolled(\context_course::instance((int)$course->id), (int)$user->id, '', true));
        self::assertSame(1, $this->enrol_count((int)$course->id, (int)$user->id));
    }

    public function test_finite_and_future_moodle_course_grants_do_not_create_valid_access(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $expired = $this->qa_user();
        $future = $this->qa_user();
        $service = new MoodleCourseEnrolmentService();
        $result = $service->apply((int)$expired->id, (int)$course->id, 'student', time()-600, time()-120, false);
        self::assertSame('created', $result['status']);
        self::assertFalse(is_enrolled(\context_course::instance((int)$course->id), (int)$expired->id, '', true));
        $scheduled = $service->apply((int)$future->id, (int)$course->id, 'student', time()+180, time()+600, false);
        self::assertSame('scheduled', $scheduled['status']);
        self::assertSame(0, $this->enrol_count((int)$course->id, (int)$future->id));
        self::assertFalse(is_enrolled(\context_course::instance((int)$course->id), (int)$future->id, '', true));
    }

    public function test_manual_native_grant_has_no_purchase_row_required_by_purchase_revoker(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->qa_user();
        $id = $this->qa_product((int)$course->id, 'NOBUY');
        (new CommerceManualProductGrantService($DB))->grant((int)$user->id, $id, (int)get_admin()->id, 'QA no purchase');
        $record = $DB->get_record('local_subs_commerce_grant', ['beneficiaryuserid' => $user->id], '*', MUST_EXIST);
        self::assertStringStartsWith('manual-u', (string)$record->purchasereference);
        self::assertFalse($DB->record_exists(CommercePersistenceSchema::TABLE_PURCHASE, ['reference' => $record->purchasereference]));
        self::assertCount(1, CommercePurchaseRightsRevocationService::create($DB)->preview((string)$record->purchasereference));
        // A preview is not a safe revocation: revoke() demands a purchase row.
        // A provenance-aware manual-grant revoker is required in a future lot.
    }

    private function qa_user(): \stdClass {
        $suffix = bin2hex(random_bytes(5));
        return $this->getDataGenerator()->create_user([
            'username' => 'cfrc22c2_' . $suffix,
            'email' => 'cfrc22c2_' . $suffix . '@example.invalid',
        ]);
    }

    private function qa_product(int $courseid, string $suffix, string $status = 'active'): int {
        global $DB;
        $now = time();
        $scopeid = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'CFR QA C22C2 ' . $suffix,
            'course_ids' => json_encode([$courseid]),
            'creation_date' => $now,
            'last_update' => $now,
        ]);
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => self::PRODUCT_PREFIX . $suffix,
            'type' => 'course_access',
            'status' => $status,
            'name' => 'CFR PHPUnit QA ' . $suffix,
            'description' => '',
            'metadatajson' => json_encode(['access' => ['scopeid' => (int)$scopeid]]),
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function qa_purchase(int $userid, string $email): string {
        global $DB;
        $now = time()-60;
        $reference = 'cfr-c22c2-' . bin2hex(random_bytes(8));
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => $reference,
            'type' => 'native',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => $email,
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 0,
            'discountminor' => 0,
            'totalminor' => 0,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $reference;
    }

    private function fulfill_purchase_course_grant(string $purchase, \stdClass $user, int $courseid): string {
        global $DB;
        $now = time()-45;
        $reference = 'ent-c22c2-' . bin2hex(random_bytes(8));
        $grant = new CommerceEntitlementGrant(
            $reference,
            $purchase,
            'item-c22c2',
            self::PRODUCT_PREFIX . 'PURCHASE',
            'course_access',
            'course:' . $courseid . ':full',
            1,
            (int)$user->id,
            (string)$user->email,
            $now,
            null,
            ['courseid' => $courseid, 'roleshortname' => 'student'],
            ['source' => 'c22c2_phpunit_purchase_fixture']
        );
        $repository = new CommerceEntitlementGrantRepository($DB, new CommerceEntitlementGrantRecordMapper());
        $persisted = (new CommerceEntitlementGrantPersister($DB, $repository))
            ->persist(new CommerceEntitlementGrantPlan($purchase, [$grant], $now), $now);
        self::assertSame(1, $persisted->get_created());
        $registry = new CommerceNativeFulfillmentHandlerRegistry([new CommerceCourseAccessFulfillmentHandler()]);
        $executor = new CommercePersistentNativeFulfillmentExecutor(
            $registry, new MoodleCommerceNativeFulfillmentPersistenceRepository()
        );
        $result = $executor->execute($grant, CommerceNativeFulfillmentContext::runtime(
            'cfr-c22c2-' . substr(hash('sha256', $reference), 0, 16),
            time(), (int)get_admin()->id, 'phpunit_c22c2'
        ));
        self::assertTrue($result->is_completed(), $result->get_message());
        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $reference]));
        return $reference;
    }

    private function enrol_count(int $courseid, int $userid): int {
        global $DB;
        return (int)$DB->count_records_sql(
            'SELECT COUNT(1) FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid ' .
            'WHERE e.courseid=:courseid AND ue.userid=:userid',
            ['courseid' => $courseid, 'userid' => $userid]
        );
    }
}
