<?php
/** CampusFR C2.2c.3 — isolated integration tests for strict manual Native course revocation. */
declare(strict_types=1);
namespace local_campusreader;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\grant\CommerceManualProductGrantService;
use local_subscriptions\commerce\grant\CommerceManualCourseGrantRevocationService;
use local_subscriptions\commerce\entitlement\lifecycle\CommerceGrantLifecycleReconciler;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseEnrolmentService;

/** @coversNothing */
final class manual_revocation_test extends \advanced_testcase {
    public function test_revoke_owned_manual_course_enrolment_and_record_audit(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $ref = $this->grant_course((int)$user->id, (int)$course->id);
        self::assertTrue($this->has_access((int)$course->id, (int)$user->id));
        $revoker = new CommerceManualCourseGrantRevocationService($DB);
        $preview = $revoker->preview($ref);
        self::assertSame('created', $preview['enrolmentstatus']);
        self::assertSame('active', $preview['status']);
        $result = $revoker->revoke($ref, (int)get_admin()->id, 'C22c3 PHPUnit QA');
        self::assertSame(1, $result['revoked']);
        self::assertSame(1, $result['unenrolled']);
        self::assertSame(0, $result['preserved']);
        self::assertFalse($this->has_access((int)$course->id, (int)$user->id));
        $grant = $DB->get_record('local_subs_commerce_grant', ['grantreference' => $ref], '*', MUST_EXIST);
        self::assertSame('revoked', $grant->status);
        $audit = json_decode((string)$grant->metadatajson, true)['rights_revocation'];
        self::assertSame('admin_explicit_manual_revocation', $audit['source']);
        self::assertSame((int)get_admin()->id, $audit['revokedbyuserid']);
    }

    public function test_revoke_is_idempotent_and_manual_grant_replay_does_not_reactivate(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $product = $this->qa_product((int)$course->id);
        $manual = new CommerceManualProductGrantService($DB);
        $issued = $manual->grant((int)$user->id, $product, (int)get_admin()->id, 'QA');
        self::assertSame(1, $issued['created']);
        $reference = $this->only_grant_reference((int)$user->id, (int)$course->id);
        $revoker = new CommerceManualCourseGrantRevocationService($DB);
        self::assertSame(1, $revoker->revoke($reference, (int)get_admin()->id, 'QA revoke')['revoked']);
        $again = $revoker->revoke($reference, (int)get_admin()->id, 'QA repeat');
        self::assertSame(0, $again['revoked']);
        self::assertSame(0, $again['unenrolled']);
        $replayed = $manual->grant((int)$user->id, $product, (int)get_admin()->id, 'QA');
        self::assertSame(0, $replayed['created']);
        self::assertSame(1, $replayed['identical']);
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $reference]));
        self::assertSame(1, $DB->count_records('local_subs_commerce_ful_attempt', ['grantreference' => $reference]));
        // A later background reconciliation must not reactivate this revoked record.
        (new CommerceGrantLifecycleReconciler($DB))->execute();
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', ['grantreference' => $reference]));
        self::assertFalse($this->has_access((int)$course->id, (int)$user->id));
    }

    public function test_overlapping_grants_preserve_access_then_last_revoke_removes_original_owned_enrolment(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $first = $this->grant_course((int)$user->id, (int)$course->id);
        $secondproduct = $this->qa_product((int)$course->id);
        (new CommerceManualProductGrantService($DB))->grant((int)$user->id, $secondproduct, (int)get_admin()->id, 'QA independent');
        $records = array_values($DB->get_records('local_subs_commerce_grant', ['beneficiaryuserid' => $user->id], 'id ASC'));
        self::assertCount(2, $records);
        $second = (string)$records[1]->grantreference;
        self::assertSame('updated', (new CommerceManualCourseGrantRevocationService($DB))->preview($second)['enrolmentstatus']);
        $service = new CommerceManualCourseGrantRevocationService($DB);
        $one = $service->revoke($first, (int)get_admin()->id, 'QA first');
        self::assertSame(1, $one['preserved']);
        self::assertTrue($this->has_access((int)$course->id, (int)$user->id));
        $two = $service->revoke($second, (int)get_admin()->id, 'QA second');
        self::assertSame(1, $two['unenrolled']);
        self::assertFalse($this->has_access((int)$course->id, (int)$user->id));
    }

    public function test_preexisting_moodle_enrolment_is_never_deleted(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $existing = (new MoodleCourseEnrolmentService())->apply(
            (int)$user->id, (int)$course->id, 'student', time()-50, null, false
        );
        self::assertSame('created', $existing['status']);
        $ref = $this->grant_course((int)$user->id, (int)$course->id);
        $service = new CommerceManualCourseGrantRevocationService($DB);
        self::assertSame('updated', $service->preview($ref)['enrolmentstatus']);
        $revoke = $service->revoke($ref, (int)get_admin()->id, 'Preserve independent enrolment');
        self::assertSame(1, $revoke['preserved']);
        self::assertSame(0, $revoke['unenrolled']);
        self::assertTrue($this->has_access((int)$course->id, (int)$user->id));
    }

    public function test_other_course_grant_is_untouched(): void {
        global $DB;
        $this->resetAfterTest(true);
        $a = $this->getDataGenerator()->create_course();
        $b = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $refa = $this->grant_course((int)$user->id, (int)$a->id);
        $this->grant_course((int)$user->id, (int)$b->id);
        $result = (new CommerceManualCourseGrantRevocationService($DB))
            ->revoke($refa, (int)get_admin()->id, 'Course A only');
        self::assertSame(1, $result['unenrolled']);
        self::assertFalse($this->has_access((int)$a->id, (int)$user->id));
        self::assertTrue($this->has_access((int)$b->id, (int)$user->id));
    }

    public function test_wrong_manual_provenance_is_rejected_without_modifying_right(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $ref = $this->grant_course((int)$user->id, (int)$course->id);
        $grant = $DB->get_record('local_subs_commerce_grant', ['grantreference' => $ref], '*', MUST_EXIST);
        $metadata = json_decode((string)$grant->metadatajson, true);
        $metadata['source'] = 'not_manual';
        $DB->set_field('local_subs_commerce_grant', 'metadatajson', json_encode($metadata), ['id' => $grant->id]);
        try {
            (new CommerceManualCourseGrantRevocationService($DB))
                ->revoke($ref, (int)get_admin()->id, 'QA invalid source');
            self::fail('Must reject an invalid source');
        } catch (\coding_exception $expected) {
            self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['id' => $grant->id]));
            self::assertTrue($this->has_access((int)$course->id, (int)$user->id));
        }
    }

    public function test_unhandled_group_side_effects_fail_closed(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $ref = $this->grant_course((int)$user->id, (int)$course->id);
        $grant = $DB->get_record('local_subs_commerce_grant', ['grantreference' => $ref], '*', MUST_EXIST);
        $config = json_decode((string)$grant->configurationjson, true);
        $config['groupids'] = [9001];
        $DB->set_field('local_subs_commerce_grant', 'configurationjson', json_encode($config), ['id' => $grant->id]);
        try {
            (new CommerceManualCourseGrantRevocationService($DB))
                ->revoke($ref, (int)get_admin()->id, 'QA group unsupported');
            self::fail('Must reject group side effects');
        } catch (\coding_exception $expected) {
            self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', ['id' => $grant->id]));
            self::assertTrue($this->has_access((int)$course->id, (int)$user->id));
        }
    }

    public function test_non_admin_actor_is_rejected(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $beneficiary = $this->getDataGenerator()->create_user();
        $actor = $this->getDataGenerator()->create_user();
        $ref = $this->grant_course((int)$beneficiary->id, (int)$course->id);
        try {
            (new CommerceManualCourseGrantRevocationService($DB))->revoke($ref, (int)$actor->id, 'No permission');
            self::fail('Non admin must be rejected');
        } catch (\coding_exception $expected) {
            self::assertTrue($this->has_access((int)$course->id, (int)$beneficiary->id));
        }
    }

    private function grant_course(int $userid, int $courseid): string {
        global $DB;
        $product = $this->qa_product($courseid);
        (new CommerceManualProductGrantService($DB))->grant($userid, $product, (int)get_admin()->id, 'C22c3 QA');
        $records = $DB->get_records('local_subs_commerce_grant', ['beneficiaryuserid' => $userid], 'id DESC');
        return (string)reset($records)->grantreference;
    }

    private function qa_product(int $courseid): int {
        global $DB;
        $now = time();
        $sku = 'QA.C22C3.' . bin2hex(random_bytes(8));
        $scope = $DB->insert_record('subscription_access_scope', (object)[
            'name' => 'CFR C22c3 test ' . $sku, 'course_ids' => json_encode([$courseid]),
            'creation_date' => $now, 'last_update' => $now,
        ]);
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => $sku, 'type' => 'course_access', 'status' => 'active',
            'name' => 'C22c3 PHPUnit only', 'description' => '',
            'metadatajson' => json_encode(['access' => ['scopeid' => (int)$scope]]),
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    private function only_grant_reference(int $userid, int $courseid): string {
        global $DB;
        $records = $DB->get_records('local_subs_commerce_grant', [
            'beneficiaryuserid' => $userid, 'resourcekey' => 'course:' . $courseid . ':full',
        ]);
        self::assertCount(1, $records);
        return (string)reset($records)->grantreference;
    }

    private function has_access(int $courseid, int $userid): bool {
        return is_enrolled(\context_course::instance($courseid), $userid, '', true);
    }
}
