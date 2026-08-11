<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_manager;
use local_subscriptions\trial_manager;

/**
 * Determines whether a Native product belongs to the Legacy Trial course scope.
 *
 * Eligibility is derived from:
 * Trial plan -> access scope course IDs -> Native product course entitlements.
 * URL parameters, bundles and digital products cannot grant Trial pricing.
 */
final class CommerceTrialProductEligibilityService {
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    public function is_eligible(int $userid, string $productsku): bool {
        if (
            $userid <= 0 ||
            trim($productsku) === '' ||
            trial_manager::user_has_active_trial($userid) === null ||
            !trial_manager::is_discount_window_open($userid)
        ) {
            return false;
        }

        return $this->eligible_course_ids($productsku) !== [];
    }

    /** @return int[] */
    public function eligible_course_ids(string $productsku): array {
        $trialcourseids = $this->trial_course_ids();
        if ($trialcourseids === []) {
            return [];
        }

        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => strtoupper(trim($productsku))],
            'id,sku,type,status',
            IGNORE_MISSING
        );
        if (
            $product === false ||
            strtolower(trim((string)$product->status)) !== 'active' ||
            !in_array(
                strtolower(trim((string)$product->type)),
                ['course_access', 'subscription'],
                true
            )
        ) {
            return [];
        }

        $entitlements = $this->db->get_records(
            'local_subs_commerce_prod_ent',
            ['productid' => (int)$product->id],
            'sortorder ASC, id ASC',
            'resourcekey,configurationjson'
        );

        $eligible = [];
        foreach ($entitlements as $entitlement) {
            $courseid = $this->course_id(
                (string)$entitlement->resourcekey,
                (string)$entitlement->configurationjson
            );
            if ($courseid > 0 && in_array($courseid, $trialcourseids, true)) {
                $eligible[$courseid] = $courseid;
            }
        }

        return array_values($eligible);
    }

    /** @return int[] */
    public function trial_course_ids(): array {
        $trialplanid = (int)get_config(
            'local_subscriptions',
            'trial_plan_id'
        );
        if ($trialplanid <= 0) {
            return [];
        }

        $scope = subscription_manager::get_access_scope_from_planid($trialplanid);
        if ($scope === null || empty($scope->course_ids)) {
            return [];
        }

        $courseids = preg_split(
            '/[,;\s]+/',
            (string)$scope->course_ids,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $courseids = array_values(array_unique(array_filter(
            array_map('intval', $courseids),
            static fn(int $courseid): bool => $courseid > 0
        )));

        return $this->related_course_ids($courseids);
    }

    /** @param int[] $courseids @return int[] */
    private function related_course_ids(array $courseids): array {
        $result = array_fill_keys($courseids, true);
        if ($result === []) {
            return [];
        }

        $fields = $this->db->get_records_select(
            'customfield_field',
            "shortname IN ('realcourseid', 'trialcourseid')",
            [],
            '',
            'id'
        );
        $fieldids = array_map(
            static fn(\stdClass $record): int => (int)$record->id,
            array_values($fields)
        );
        if ($fieldids === []) {
            return array_keys($result);
        }

        [$fieldsql, $fieldparams] = $this->db->get_in_or_equal(
            $fieldids,
            SQL_PARAMS_NAMED,
            'trialfield'
        );
        [$instancesql, $instanceparams] = $this->db->get_in_or_equal(
            array_keys($result),
            SQL_PARAMS_NAMED,
            'trialinstance'
        );
        [$valuesql, $valueparams] = $this->db->get_in_or_equal(
            array_keys($result),
            SQL_PARAMS_NAMED,
            'trialvalue'
        );

        $records = $this->db->get_records_sql(
            "SELECT d.id, d.instanceid, d.value
               FROM {customfield_data} d
              WHERE d.fieldid {$fieldsql}
                AND (
                    d.instanceid {$instancesql}
                    OR CAST(d.value AS UNSIGNED) {$valuesql}
                )",
            $fieldparams + $instanceparams + $valueparams
        );

        foreach ($records as $record) {
            if ((int)$record->instanceid > 0) {
                $result[(int)$record->instanceid] = true;
            }
            if ((int)$record->value > 0) {
                $result[(int)$record->value] = true;
            }
        }

        return array_map('intval', array_keys($result));
    }

    private function course_id(string $resourcekey, string $configurationjson): int {
        if (preg_match('/^course:(\d+)(?::|$)/i', trim($resourcekey), $matches)) {
            return (int)$matches[1];
        }

        $configuration = json_decode($configurationjson, true);
        if (!is_array($configuration)) {
            return 0;
        }

        return (int)($configuration['courseid'] ?? $configuration['course_id'] ?? 0);
    }
}
