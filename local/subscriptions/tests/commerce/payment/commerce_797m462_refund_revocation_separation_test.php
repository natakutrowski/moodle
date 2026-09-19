<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\commerce\fulfillment\native\course\service\MoodleCourseEnrolmentService;

final class commerce_797m462_refund_revocation_separation_test extends \advanced_testcase {
    public function test_refund_and_provider_import_are_financial_only(): void {
        global $CFG;

        $command = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundCommand.php'
        );
        $import = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundImportService.php'
        );
        $paypal = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/paypal/webhook/PayPalWebhookService.php'
        );
        $legacyLifecycle = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/payment/refund/CommercePaymentRefundLifecycleService.php'
        );

        self::assertIsString($command);
        self::assertIsString($import);
        self::assertIsString($paypal);
        self::assertIsString($legacyLifecycle);
        self::assertStringNotContainsString('->reconcile_full_refund($paymentid)', $command);
        self::assertStringNotContainsString('->reconcile_full_refund($paymentid)', $import);
        self::assertStringNotContainsString('CommercePaymentRefundLifecycleService', $command);
        self::assertStringNotContainsString('CommercePaymentRefundLifecycleService', $import);
        self::assertStringNotContainsString('CommercePaymentRefundLifecycleService', $paypal);
        self::assertStringContainsString('Refunds are financial-only', $command);
        self::assertStringContainsString('never revoke customer rights automatically', $import);
        self::assertStringContainsString('Provider refund webhooks synchronize finance only', $paypal);
        self::assertStringNotContainsString('terminate_purchase(', $legacyLifecycle);
        self::assertStringNotContainsString("status = 'revoked'", $legacyLifecycle);
        self::assertStringContainsString('performs no rights', $legacyLifecycle);
    }

    public function test_admin_refund_defaults_to_keep_rights_and_standalone_revoke_exists(): void {
        global $CFG;

        $refund = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/refund.php'
        );
        $view = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/view.php'
        );
        $revoke = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/purchases/revoke_rights.php'
        );

        self::assertIsString($refund);
        self::assertIsString($view);
        self::assertIsString($revoke);
        self::assertStringContainsString("'revokeaccess'", $refund);
        self::assertStringContainsString("'1',\n            false", $refund);
        self::assertStringContainsString('CommercePurchaseRightsRevocationService', $refund);
        self::assertStringContainsString('CommercePedagogicalParticipationRepository::REFUNDED', $refund);
        self::assertStringContainsString('revoke_rights.php', $view);
        self::assertStringContainsString('confirmrevoke', $revoke);
    }

    public function test_bundle_component_digital_revocation_is_provenance_scoped(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $bundle = $this->purchase((int)$user->id, 'cmp_m462_bundle', $now);
        $direct = $this->purchase((int)$user->id, 'cmp_m462_direct', $now + 1);

        $bundlegrant = $this->grant(
            $bundle,
            (int)$user->id,
            'BUNDLE.TEST',
            'DIGITAL.CHILD',
            'digital_download',
            'digital-product:91001',
            $now
        );
        $directgrant = $this->grant(
            $direct,
            (int)$user->id,
            'DIGITAL.CHILD',
            'DIGITAL.CHILD',
            'digital_download',
            'digital-product:91001',
            $now + 1
        );
        $bundleaccess = $this->digital_access($bundlegrant, $bundle, (int)$user->id, 'DIGITAL.CHILD', $now);
        $directaccess = $this->digital_access($directgrant, $direct, (int)$user->id, 'DIGITAL.CHILD', $now + 1);

        $result = CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $bundle,
            (int)$user->id,
            'bundle refund decision',
            $now + 10
        );

        self::assertSame(1, $result['revoked']);
        self::assertSame(1, $result['digital']);
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $bundlegrant,
        ]));
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_dig_access', 'status', [
            'id' => $bundleaccess,
        ]));
        self::assertSame('active', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $directgrant,
        ]));
        self::assertSame('active', $DB->get_field('local_subs_commerce_dig_access', 'status', [
            'id' => $directaccess,
        ]));
    }

    public function test_course_revocation_preserves_preexisting_manual_enrolment(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $manual = enrol_get_plugin('manual');
        self::assertNotFalse($manual);
        $instance = null;
        foreach (enrol_get_instances((int)$course->id, true) as $candidate) {
            if ($candidate->enrol === 'manual' && (int)$candidate->status === ENROL_INSTANCE_ENABLED) {
                $instance = $candidate;
                break;
            }
        }
        if ($instance === null) {
            $instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }
        $instanceid = (int)$instance->id;
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $manual->enrol_user($instance, (int)$user->id, $roleid, $now - 100, 0, ENROL_USER_ACTIVE);

        $purchase = $this->purchase((int)$user->id, 'cmp_m462_preexisting', $now);
        $grantreference = $this->grant(
            $purchase,
            (int)$user->id,
            'COURSE.TEST',
            'COURSE.TEST',
            'course_access',
            'course:' . $course->id . ':full',
            $now,
            ['courseid' => (int)$course->id, 'roleshortname' => 'student']
        );
        $this->fulfillment_state($grantreference, [
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'enrolment' => [
                'status' => 'updated',
                'enrolid' => (int)$instanceid,
            ],
        ], $now);

        CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $purchase,
            (int)$user->id,
            'keep pre-existing enrolment',
            $now + 10
        );

        self::assertTrue($DB->record_exists('user_enrolments', [
            'enrolid' => (int)$instanceid,
            'userid' => (int)$user->id,
        ]));
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $grantreference,
        ]));
    }

    public function test_course_revocation_restores_role_removed_from_preexisting_enrolment(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/lib/accesslib.php');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $manual = enrol_get_plugin('manual');
        self::assertNotFalse($manual);
        $instance = null;
        foreach (enrol_get_instances((int)$course->id, true) as $candidate) {
            if ($candidate->enrol === 'manual' && (int)$candidate->status === ENROL_INSTANCE_ENABLED) {
                $instance = $candidate;
                break;
            }
        }
        if ($instance === null) {
            $instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        $trialroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($trialroleid <= 0) {
            $trialroleid = create_role('M4.6.2 trial student', 'trialstudent', 'M4.6.2 test role');
        }
        $grammarroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'grammarstudent'], IGNORE_MISSING);
        if ($grammarroleid <= 0) {
            $grammarroleid = create_role('M4.6.2 grammar student', 'grammarstudent', 'M4.6.2 test role');
        }

        $manual->enrol_user($instance, (int)$user->id, $trialroleid, $now - 100, 0, ENROL_USER_ACTIVE);
        $context = \context_course::instance((int)$course->id);
        self::assertTrue(user_has_role_assignment((int)$user->id, $trialroleid, $context->id));

        role_unassign($trialroleid, (int)$user->id, $context->id);
        role_assign($grammarroleid, (int)$user->id, $context->id);

        $purchase = $this->purchase((int)$user->id, 'cmp_m462_restore_role', $now);
        $grantreference = $this->grant(
            $purchase,
            (int)$user->id,
            'COURSE.GRAMMAR',
            'COURSE.GRAMMAR',
            'course_access',
            'course:' . $course->id . ':grammar',
            $now,
            ['courseid' => (int)$course->id, 'roleshortname' => 'grammarstudent']
        );
        $this->fulfillment_state($grantreference, [
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'enrolment' => [
                'status' => 'updated',
                'enrolid' => (int)$instance->id,
            ],
            'role' => [
                'status' => 'assigned',
                'roleid' => $grammarroleid,
                'roleshortname' => 'grammarstudent',
                'removed' => ['trialstudent'],
            ],
        ], $now);

        CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $purchase,
            (int)$user->id,
            'restore pre-existing Moodle role',
            $now + 10
        );

        self::assertTrue($DB->record_exists('user_enrolments', [
            'enrolid' => (int)$instance->id,
            'userid' => (int)$user->id,
        ]));
        self::assertFalse(user_has_role_assignment((int)$user->id, $grammarroleid, $context->id));
        self::assertTrue(user_has_role_assignment((int)$user->id, $trialroleid, $context->id));
        self::assertSame('revoked', $DB->get_field('local_subs_commerce_grant', 'status', [
            'grantreference' => $grantreference,
        ]));
    }

    public function test_course_revocation_removes_commerce_created_enrolment_when_no_other_right_remains(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $now = time();

        $enrolment = (new MoodleCourseEnrolmentService())->apply(
            (int)$user->id,
            (int)$course->id,
            'student',
            $now,
            null,
            false
        );
        self::assertSame('created', $enrolment['status']);

        $purchase = $this->purchase((int)$user->id, 'cmp_m462_created', $now);
        $grantreference = $this->grant(
            $purchase,
            (int)$user->id,
            'COURSE.TEST2',
            'COURSE.TEST2',
            'course_access',
            'course:' . $course->id . ':full',
            $now,
            ['courseid' => (int)$course->id, 'roleshortname' => 'student']
        );
        $this->fulfillment_state($grantreference, [
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'enrolment' => $enrolment,
        ], $now);

        CommercePurchaseRightsRevocationService::create($DB)->revoke(
            $purchase,
            (int)$user->id,
            'remove commerce enrolment',
            $now + 10
        );

        self::assertFalse($DB->record_exists('user_enrolments', [
            'enrolid' => (int)$enrolment['enrolid'],
            'userid' => (int)$user->id,
        ]));
    }

    public function test_service_explicitly_handles_promotion_join_and_ownership_fallback_respects_revocation(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/purchase/revocation/CommercePurchaseRightsRevocationService.php'
        );
        $ownership = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php'
        );

        self::assertIsString($service);
        self::assertIsString($ownership);
        self::assertStringContainsString('CommercePedagogicalPromotionJoinGrant::GRANT_TYPE', $service);
        self::assertStringContainsString('terminate_purchase(', $service);
        self::assertStringContainsString('detach_promotion_join_access', $service);
        self::assertStringContainsString("\$access->status = 'revoked'", $service);
        self::assertStringContainsString('NOT EXISTS (', $ownership);
        self::assertStringContainsString('g.type <> :promotionjointype', $ownership);
    }

    private function purchase(int $userid, string $reference, int $now): string {
        global $DB;
        $uuid = bin2hex(random_bytes(16));
        $DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => $uuid,
            'reference' => $reference,
            'type' => 'native',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => 'm462-' . $userid . '@example.invalid',
            'status' => 'fulfilled',
            'currency' => 'EUR',
            'subtotalminor' => 1000,
            'discountminor' => 0,
            'totalminor' => 1000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $reference;
    }

    private function grant(
        string $purchase,
        int $userid,
        string $itemreference,
        string $productsku,
        string $type,
        string $resourcekey,
        int $now,
        array $configuration = []
    ): string {
        global $DB;
        $reference = 'ent-' . substr(hash('sha256', $purchase . '|' . $type . '|' . random_int(1, PHP_INT_MAX)), 0, 32);
        $DB->insert_record('local_subs_commerce_grant', (object)[
            'grantreference' => $reference,
            'idempotencykey' => 'idem-' . substr(hash('sha256', $reference), 0, 40),
            'purchasereference' => $purchase,
            'itemreference' => $itemreference,
            'productsku' => $productsku,
            'type' => $type,
            'resourcekey' => $resourcekey,
            'quantity' => 1,
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'm462-' . $userid . '@example.invalid',
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

    private function digital_access(
        string $grantreference,
        string $purchase,
        int $userid,
        string $productsku,
        int $now
    ): int {
        global $DB;
        return (int)$DB->insert_record('local_subs_commerce_dig_access', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => 'dig-' . substr(hash('sha256', $grantreference), 0, 40),
            'purchasereference' => $purchase,
            'productsku' => $productsku,
            'resourcekey' => 'digital-product:91001',
            'beneficiaryuserid' => $userid,
            'beneficiaryemail' => 'm462-' . $userid . '@example.invalid',
            'downloadtoken' => bin2hex(random_bytes(32)),
            'maxdownloads' => null,
            'downloadcount' => 0,
            'validfrom' => $now,
            'validuntil' => null,
            'status' => 'active',
            'lastdownloadat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function fulfillment_state(string $grantreference, array $payload, int $now): void {
        global $DB;
        $grant = $DB->get_record('local_subs_commerce_grant', ['grantreference' => $grantreference], '*', MUST_EXIST);
        $DB->insert_record('local_subs_commerce_ful_state', (object)[
            'grantreference' => $grantreference,
            'idempotencykey' => (string)$grant->idempotencykey,
            'granttype' => (string)$grant->type,
            'handlerclass' => 'm462-test',
            'status' => 'completed',
            'attempts' => 1,
            'lastexecutionreference' => 'm462-test',
            'lastsource' => 'phpunit',
            'lastactoruserid' => null,
            'lastpayloadjson' => json_encode($payload),
            'lastmessage' => 'completed',
            'lasterrorclass' => null,
            'timecreated' => $now,
            'timestarted' => $now,
            'timecompleted' => $now,
            'timemodified' => $now,
        ]);
    }
}
