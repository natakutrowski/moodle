<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\bundle;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\digital\CommerceNativeDigitalDownloadResolver;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use moodle_database;

final class CommerceBundlePurchaseCertifier {
    public function __construct(private readonly moodle_database $database) {
    }

    public function certify(string $reference, string $expectedscenario = 'auto'): CommerceBundlePurchaseCertificationReport {
        $reference = trim($reference);
        $expectedscenario = $this->normalise_expected_scenario($expectedscenario);
        $checks = [];

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $reference],
            '*',
            IGNORE_MISSING
        );
        $this->add($checks, 'purchase', $purchase !== false,
            $purchase === false ? 'Purchase not found.' : 'Purchase found.');
        if ($purchase === false) {
            return new CommerceBundlePurchaseCertificationReport($reference, 'unknown', false, $checks);
        }

        $items = array_values($this->database->get_records(
            CommercePersistenceSchema::TABLE_ITEM,
            ['purchaseid' => (int)$purchase->id],
            'position ASC, id ASC'
        ));
        $grants = array_values($this->database->get_records(
            'local_subs_commerce_grant',
            ['purchasereference' => $reference],
            'id ASC'
        ));
        $scenario = self::detect_scenario(array_map(
            static fn(\stdClass $grant): string => (string)$grant->type,
            $grants
        ));

        $this->add($checks, 'bundle_shape', count($items) >= 1 && count($grants) >= 2,
            count($items) . ' item(s) and ' . count($grants) . ' Grant(s) found.', [
                'purchase_type' => (string)$purchase->type,
                'item_count' => count($items),
                'grant_count' => count($grants),
                'scenario' => $scenario,
            ]);
        $this->add($checks, 'scenario', $scenario !== 'unknown' && ($expectedscenario === 'auto' || $scenario === $expectedscenario),
            $expectedscenario === 'auto'
                ? 'Detected bundle scenario: ' . $scenario . '.'
                : 'Detected scenario ' . $scenario . '; expected ' . $expectedscenario . '.',
            ['detected' => $scenario, 'expected' => $expectedscenario]);
        $this->add($checks, 'purchase_status', (string)$purchase->status === 'fulfilled',
            'Purchase status is ' . (string)$purchase->status . '.', ['expected' => 'fulfilled']);

        $paid = $this->database->record_exists(
            CommercePersistenceSchema::TABLE_PAYMENT,
            ['purchaseid' => (int)$purchase->id, 'status' => 'paid']
        );
        $this->add($checks, 'payment', $paid,
            $paid ? 'Paid Native payment found.' : 'No paid Native payment found.');

        $resolver = new CommerceNativeDigitalDownloadResolver($this->database);
        $coursecount = 0;
        $digitalcount = 0;
        $allactive = $grants !== [];
        $allcompleted = $grants !== [];
        $allattempted = $grants !== [];
        $allresources = $grants !== [];
        $grantdetails = [];

        foreach ($grants as $grant) {
            $type = (string)$grant->type;
            $state = $this->database->get_record('local_subs_commerce_ful_state', [
                'grantreference' => (string)$grant->grantreference,
            ], '*', IGNORE_MISSING);
            $completedattempt = $this->database->record_exists('local_subs_commerce_ful_attempt', [
                'grantreference' => (string)$grant->grantreference,
                'status' => 'completed',
            ]);
            $resourcevalid = false;
            $resourcedetails = [];

            if ($type === 'course_access') {
                $coursecount++;
                $courseid = $this->course_id((string)$grant->resourcekey, (string)$grant->configurationjson);
                $userid = $grant->beneficiaryuserid === null ? (int)($purchase->userid ?? 0) : (int)$grant->beneficiaryuserid;
                $resourcevalid = $courseid > 0 && $userid > 0 && $this->has_active_enrolment($userid, $courseid);
                $resourcedetails = ['course_id' => $courseid, 'user_id' => $userid, 'enrolled' => $resourcevalid];
            } else if ($type === 'digital_download') {
                $digitalcount++;
                $access = $this->database->get_record('local_subs_commerce_dig_access', [
                    'grantreference' => (string)$grant->grantreference,
                ], '*', IGNORE_MISSING);
                $error = null;
                if ($access !== false) {
                    try {
                        $resolver->resolve((string)$access->downloadtoken, time());
                        $resourcevalid = (string)$access->status === 'active';
                    } catch (\Throwable $exception) {
                        $error = $exception->getMessage();
                    }
                }
                $resourcedetails = [
                    'access_status' => $access === false ? null : (string)$access->status,
                    'token_present' => $access !== false && trim((string)$access->downloadtoken) !== '',
                    'downloadable' => $resourcevalid,
                    'error' => $error,
                ];
            }

            $active = (string)$grant->status === 'active';
            $completed = $state !== false && (string)$state->status === 'completed';
            $allactive = $allactive && $active;
            $allcompleted = $allcompleted && $completed;
            $allattempted = $allattempted && $completedattempt;
            $allresources = $allresources && $resourcevalid;

            $grantdetails[] = [
                'grant_reference' => (string)$grant->grantreference,
                'type' => $type,
                'status' => (string)$grant->status,
                'resource_key' => (string)$grant->resourcekey,
                'fulfillment_status' => $state === false ? null : (string)$state->status,
                'completed_attempt' => $completedattempt,
                'resource' => $resourcedetails,
            ];
        }

        $this->add($checks, 'grant_lifecycle', $allactive,
            $allactive ? 'All bundle Grants are active.' : 'At least one bundle Grant is not active.', $grantdetails);
        $this->add($checks, 'fulfillment_state', $allcompleted,
            $allcompleted ? 'All bundle fulfillments are completed.' : 'At least one bundle fulfillment is not completed.', $grantdetails);
        $this->add($checks, 'fulfillment_attempt', $allattempted,
            $allattempted ? 'Each bundle Grant has a completed attempt.' : 'At least one bundle Grant has no completed attempt.', $grantdetails);
        $this->add($checks, 'delivered_resources', $allresources,
            $allresources ? 'All bundle resources are really delivered.' : 'At least one bundle resource is not really delivered.', [
                'course_grants' => $coursecount,
                'digital_grants' => $digitalcount,
                'grants' => $grantdetails,
            ]);

        $crm = (new CommercePurchaseReadRepository($this->database))->find_by_reference($reference);
        $crmvalid = count($grants) >= 2
            && $crm !== null
            && count($crm->grants) === count($grants)
            && count($crm->fulfillments) === count($grants)
            && count($crm->fulfillmentattempts) >= count($grants);
        $this->add($checks, 'crm_read_model', $crmvalid,
            $crmvalid ? 'CRM Native Read Model contains all bundle operations.' : 'CRM Native Read Model is incomplete.',
            $crm === null ? [] : [
                'grant_count' => count($crm->grants),
                'fulfillment_count' => count($crm->fulfillments),
                'attempt_count' => count($crm->fulfillmentattempts),
            ]);

        $certified = !array_filter($checks, static fn(array $check): bool => $check['status'] !== 'PASS');
        return new CommerceBundlePurchaseCertificationReport($reference, $scenario, $certified, $checks);
    }

    public static function detect_scenario(array $granttypes): string {
        $coursecount = count(array_filter($granttypes, static fn(string $type): bool => $type === 'course_access'));
        $digitalcount = count(array_filter($granttypes, static fn(string $type): bool => $type === 'digital_download'));
        $total = count($granttypes);

        if ($total >= 2 && $coursecount === $total) {
            return 'courses';
        }
        if ($total >= 2 && $digitalcount === $total) {
            return 'digitals';
        }
        if ($coursecount >= 1 && $digitalcount >= 1 && ($coursecount + $digitalcount) === $total) {
            return 'mixed';
        }
        return 'unknown';
    }

    private function normalise_expected_scenario(string $scenario): string {
        $scenario = strtolower(trim($scenario));
        return in_array($scenario, ['auto', 'mixed', 'courses', 'digitals'], true) ? $scenario : 'invalid';
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
