<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\accessscope;

defined('MOODLE_INTERNAL') || die();

/** Resolves Native product -> Legacy plan -> Access scope without merging their identities. */
final class CommerceAccessScopeRelationRepository {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function find_for_native_product(int $productid): CommerceAccessScopeRelation {
        $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
            'productid' => $productid,
            'legacytable' => 'subscription_plan',
        ]);

        if (!$mapping) {
            return new CommerceAccessScopeRelation(null, null, null, null, [], 'native_unmapped');
        }

        return $this->find_for_plan((int)$mapping->legacyid, 'native_mapping');
    }

    public function find_for_plan(int $planid, string $source = 'legacy_plan'): CommerceAccessScopeRelation {
        $plan = $this->db->get_record('subscription_plan', ['id' => $planid]);
        if (!$plan) {
            return new CommerceAccessScopeRelation(null, null, null, null, [], $source);
        }

        $scope = null;
        if (!empty($plan->accessscopeid)) {
            $scope = $this->db->get_record('subscription_access_scope', ['id' => $plan->accessscopeid]);
        }

        $courses = [];
        if ($scope) {
            foreach ($this->parse_course_ids((string)$scope->course_ids) as $courseid) {
                $course = $this->db->get_record('course', ['id' => $courseid], 'id,fullname,shortname');
                $courses[] = [
                    'id' => $courseid,
                    'fullname' => $course ? (string)$course->fullname : ('#' . $courseid),
                    'shortname' => $course ? (string)$course->shortname : '',
                ];
            }
        }

        return new CommerceAccessScopeRelation(
            (int)$plan->id,
            (string)$plan->name,
            $scope ? (int)$scope->id : null,
            $scope ? (string)$scope->name : null,
            $courses,
            $source
        );
    }

    private function parse_course_ids(string $value): array {
        $decoded = json_decode($value, true);
        $values = is_array($decoded) ? $decoded : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $ids = array_values(array_unique(array_filter(array_map('intval', $values), static fn(int $id): bool => $id > 0)));
        sort($ids);
        return $ids;
    }
}
