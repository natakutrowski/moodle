<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\course;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use moodle_database;

final class CommerceCoursePurchaseCertifier {
    private readonly CommercePurchaseReadRepository $readrepository;

    public function __construct(
        private readonly moodle_database $database,
        ?CommercePurchaseReadRepository $readrepository = null
    ) {
        $this->readrepository = $readrepository ?? new CommercePurchaseReadRepository($database);
    }

    public function certify(string $purchasereference): CommerceCoursePurchaseCertificationReport {
        $purchasereference = trim($purchasereference);
        $checks = [];

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $purchasereference],
            '*',
            IGNORE_MISSING
        );
        $this->add($checks, 'purchase', $purchase !== false,
            $purchase === false ? 'Purchase not found.' : 'Purchase found.',
            $purchase === false ? [] : ['id' => (int)$purchase->id, 'status' => (string)$purchase->status, 'type' => (string)$purchase->type]
        );
        if ($purchase === false) {
            return new CommerceCoursePurchaseCertificationReport($purchasereference, false, $checks);
        }

        $this->add($checks, 'purchase_status', (string)$purchase->status === 'fulfilled',
            'Purchase status is ' . (string)$purchase->status . '.', ['expected' => 'fulfilled']);

        $payments = array_values($this->database->get_records(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => (int)$purchase->id],
            'sequence ASC, id ASC'
        ));
        $paidpayments = array_values(array_filter($payments, static fn(\stdClass $payment): bool => (string)$payment->status === 'paid'));
        $this->add($checks, 'payment', $paidpayments !== [],
            $paidpayments === [] ? 'No paid Native payment found.' : 'Paid Native payment found.',
            ['count' => count($payments), 'paid_count' => count($paidpayments), 'providers' => array_values(array_unique(array_filter(array_map(static fn(\stdClass $p): ?string => $p->provider === null ? null : (string)$p->provider, $payments))))]
        );

        $grants = array_values($this->database->get_records('local_subs_commerce_grant', [
            'purchasereference' => $purchasereference,
            'type' => 'course_access',
        ], 'id ASC'));
        $this->add($checks, 'course_grants', $grants !== [],
            $grants === [] ? 'No course_access Grant found.' : count($grants) . ' course_access Grant(s) found.',
            ['count' => count($grants)]);

        $allgrantsactive = $grants !== [];
        $allstatescompleted = $grants !== [];
        $allattemptscompleted = $grants !== [];
        $allenrolled = $grants !== [];
        $grantdetails = [];

        foreach ($grants as $grant) {
            $state = $this->database->get_record('local_subs_commerce_ful_state', [
                'grantreference' => (string)$grant->grantreference,
            ], '*', IGNORE_MISSING);
            $attempts = array_values($this->database->get_records('local_subs_commerce_ful_attempt', [
                'grantreference' => (string)$grant->grantreference,
            ], 'id ASC'));
            $completedattempts = array_values(array_filter($attempts, static fn(\stdClass $attempt): bool => (string)$attempt->status === 'completed'));
            $courseid = $this->course_id((string)$grant->resourcekey, (string)$grant->configurationjson);
            $userid = $grant->beneficiaryuserid === null ? (int)($purchase->userid ?? 0) : (int)$grant->beneficiaryuserid;
            $enrolled = $courseid > 0 && $userid > 0 && $this->has_active_enrolment($userid, $courseid);

            $active = (string)$grant->status === 'active';
            $completed = $state !== false && (string)$state->status === 'completed';
            $attemptcompleted = $completedattempts !== [];
            $allgrantsactive = $allgrantsactive && $active;
            $allstatescompleted = $allstatescompleted && $completed;
            $allattemptscompleted = $allattemptscompleted && $attemptcompleted;
            $allenrolled = $allenrolled && $enrolled;

            $grantdetails[] = [
                'grant_reference' => (string)$grant->grantreference,
                'grant_status' => (string)$grant->status,
                'resource_key' => (string)$grant->resourcekey,
                'course_id' => $courseid,
                'beneficiary_user_id' => $userid,
                'fulfillment_status' => $state === false ? null : (string)$state->status,
                'attempt_count' => count($attempts),
                'completed_attempt_count' => count($completedattempts),
                'enrolled' => $enrolled,
            ];
        }

        $this->add($checks, 'grant_lifecycle', $allgrantsactive,
            $allgrantsactive ? 'All course Grants are active.' : 'At least one course Grant is not active.', $grantdetails);
        $this->add($checks, 'fulfillment_state', $allstatescompleted,
            $allstatescompleted ? 'All course fulfillments are completed.' : 'At least one course fulfillment is not completed.', $grantdetails);
        $this->add($checks, 'fulfillment_attempt', $allattemptscompleted,
            $allattemptscompleted ? 'Each course Grant has a completed attempt.' : 'At least one course Grant has no completed attempt.', $grantdetails);
        $this->add($checks, 'moodle_enrolment', $allenrolled,
            $allenrolled ? 'All beneficiaries have an active Moodle enrolment.' : 'At least one active Moodle enrolment is missing.', $grantdetails);

        $details = $this->readrepository->find_by_reference($purchasereference);
        $crmvalid = $details !== null
            && $details->summary->reference === $purchasereference
            && $details->grants !== []
            && $details->fulfillments !== []
            && $details->fulfillmentattempts !== [];
        $this->add($checks, 'crm_read_model', $crmvalid,
            $crmvalid ? 'CRM Native Read Model is complete.' : 'CRM Native Read Model is incomplete.',
            $details === null ? [] : [
                'commercial_status' => $details->summary->commercialstatus,
                'payment_status' => $details->summary->paymentstatus,
                'fulfillment_status' => $details->summary->fulfillmentstatus,
                'grant_count' => count($details->grants),
                'fulfillment_count' => count($details->fulfillments),
                'attempt_count' => count($details->fulfillmentattempts),
            ]);

        $certified = !array_filter($checks, static fn(array $check): bool => $check['status'] !== 'PASS');
        return new CommerceCoursePurchaseCertificationReport($purchasereference, $certified, $checks);
    }

    private function add(array &$checks, string $key, bool $pass, string $message, array $details = []): void {
        $checks[] = ['key' => $key, 'status' => $pass ? 'PASS' : 'FAIL', 'message' => $message, 'details' => $details];
    }

    private function course_id(string $resourcekey, string $configurationjson): int {
        if (preg_match('/^course:(\d+)(?::|$)/', trim($resourcekey), $matches)) {
            return (int)$matches[1];
        }
        $configuration = json_decode($configurationjson, true);
        return is_array($configuration) ? (int)($configuration['courseid'] ?? 0) : 0;
    }

    private function has_active_enrolment(int $userid, int $courseid): bool {
        $sql = "SELECT 1
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid
                   AND e.courseid = :courseid
                   AND ue.status = :active
                   AND e.status = :enabled";
        return $this->database->record_exists_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'active' => ENROL_USER_ACTIVE,
            'enabled' => ENROL_INSTANCE_ENABLED,
        ]);
    }
}
