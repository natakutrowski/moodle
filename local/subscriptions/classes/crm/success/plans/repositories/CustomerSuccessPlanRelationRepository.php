<?php

namespace local_subscriptions\crm\success\plans\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanObjectRelation;

/**
 * Persists and reads relations attached to Customer Success plan steps.
 */
final class CustomerSuccessPlanRelationRepository {

    private const TABLE =
        'local_subscriptions_cs_relation';

    private const STEP_TABLE =
        'local_subscriptions_cs_step';

    public function add(
        int $planid,
        int $stepid,
        string $objecttype,
        int $objectid,
        string $relation,
        int $actorid
    ): int {
        global $DB;

        if (
            $planid <= 0 ||
            $stepid <= 0 ||
            $objectid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Customer Success relation IDs must be greater than zero.'
            );
        }

        if ($actorid < 0) {
            throw new \InvalidArgumentException(
                'Customer Success relation actor ID cannot be negative.'
            );
        }

        if (
            !CustomerSuccessPlanRelationType::is_valid(
                $objecttype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success relation object type.'
            );
        }

        if (
            !CustomerSuccessPlanRelation::is_valid(
                $relation
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success semantic relation.'
            );
        }

        $step = $DB->get_record(
            self::STEP_TABLE,
            [
                'id' => $stepid,
            ],
            'id, planid',
            MUST_EXIST
        );

        if ((int)$step->planid !== $planid) {
            throw new \InvalidArgumentException(
                'The Customer Success relation plan does not match the step plan.'
            );
        }

        $conditions = [
            'stepid' =>
                $stepid,

            'objecttype' =>
                $objecttype,

            'objectid' =>
                $objectid,

            'relation' =>
                $relation,
        ];

        $existing = $DB->get_record(
            self::TABLE,
            $conditions,
            'id, planid',
            IGNORE_MISSING
        );

        if ($existing) {
            if ((int)$existing->planid !== $planid) {
                throw new \coding_exception(
                    'Existing Customer Success relation has an inconsistent plan ID.'
                );
            }

            return (int)$existing->id;
        }

        return (int)$DB->insert_record(
            self::TABLE,
            (object)[
                'planid' =>
                    $planid,

                'stepid' =>
                    $stepid,

                'objecttype' =>
                    $objecttype,

                'objectid' =>
                    $objectid,

                'relation' =>
                    $relation,

                'createdby' =>
                    $actorid,

                'timecreated' =>
                    time(),
            ]
        );
    }

    public function remove(
        int $stepid,
        string $objecttype,
        int $objectid,
        ?string $relation = null
    ): void {
        global $DB;

        if (
            $stepid <= 0 ||
            $objectid <= 0
        ) {
            return;
        }

        if (
            !CustomerSuccessPlanRelationType::is_valid(
                $objecttype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success relation object type.'
            );
        }

        if (
            $relation !== null &&
            !CustomerSuccessPlanRelation::is_valid(
                $relation
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success semantic relation.'
            );
        }

        $conditions = [
            'stepid' =>
                $stepid,

            'objecttype' =>
                $objecttype,

            'objectid' =>
                $objectid,
        ];

        if ($relation !== null) {
            $conditions['relation'] =
                $relation;
        }

        $DB->delete_records(
            self::TABLE,
            $conditions
        );
    }

    /**
     * @return CustomerSuccessPlanObjectRelation[]
     */
    public function get_for_step(
        int $stepid
    ): array {
        global $DB;

        if ($stepid <= 0) {
            return [];
        }

        $records = $DB->get_records(
            self::TABLE,
            [
                'stepid' => $stepid,
            ],
            'id ASC'
        );

        return array_map(
            fn(\stdClass $record):
                CustomerSuccessPlanObjectRelation =>
                    $this->map(
                        $record
                    ),
            array_values($records)
        );
    }

    /**
     * @return CustomerSuccessPlanObjectRelation[]
     */
    public function get_for_plan(
        int $planid
    ): array {
        global $DB;

        if ($planid <= 0) {
            return [];
        }

        $records = $DB->get_records(
            self::TABLE,
            [
                'planid' => $planid,
            ],
            'stepid ASC, id ASC'
        );

        return array_map(
            fn(\stdClass $record):
                CustomerSuccessPlanObjectRelation =>
                    $this->map(
                        $record
                    ),
            array_values($records)
        );
    }

    /**
     * Finds all steps related to one CRM object.
     *
     * @return int[]
     */
    public function find_steps_for_object(
        string $objecttype,
        int $objectid,
        ?string $relation = null,
        ?int $planid = null
    ): array {
        global $DB;

        if ($objectid <= 0) {
            return [];
        }

        if (
            !CustomerSuccessPlanRelationType::is_valid(
                $objecttype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success relation object type.'
            );
        }

        if (
            $relation !== null &&
            !CustomerSuccessPlanRelation::is_valid(
                $relation
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success semantic relation.'
            );
        }

        if (
            $planid !== null &&
            $planid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Customer Success plan ID must be greater than zero.'
            );
        }

        $conditions = [
            'objecttype = :objecttype',
            'objectid = :objectid',
        ];

        $params = [
            'objecttype' =>
                $objecttype,

            'objectid' =>
                $objectid,
        ];

        if ($relation !== null) {
            $conditions[] =
                'relation = :relation';

            $params['relation'] =
                $relation;
        }

        if ($planid !== null) {
            $conditions[] =
                'planid = :planid';

            $params['planid'] =
                $planid;
        }

        $records = $DB->get_records_select(
            self::TABLE,
            implode(
                ' AND ',
                $conditions
            ),
            $params,
            'stepid ASC, id ASC',
            'id, stepid'
        );

        return array_values(
            array_unique(
                array_map(
                    static fn(
                        \stdClass $record
                    ): int =>
                        (int)$record->stepid,
                    $records
                )
            )
        );
    }

    /**
     * Finds one unique related step.
     *
     * Returns null when no relation exists.
     * Throws when several steps match.
     */
    public function find_unique_step_for_object(
        string $objecttype,
        int $objectid,
        ?string $relation = null,
        ?int $planid = null
    ): ?int {
        $stepids =
            $this->find_steps_for_object(
                objecttype:
                    $objecttype,

                objectid:
                    $objectid,

                relation:
                    $relation,

                planid:
                    $planid
            );

        if ($stepids === []) {
            return null;
        }

        if (count($stepids) > 1) {
            throw new \coding_exception(
                'Several Customer Success plan steps are related to the requested object.'
            );
        }

        return $stepids[0];
    }

    private function map(
        \stdClass $record
    ): CustomerSuccessPlanObjectRelation {
        return new CustomerSuccessPlanObjectRelation(
            id:
                (int)$record->id,

            planid:
                (int)$record->planid,

            stepid:
                (int)$record->stepid,

            objecttype:
                (string)$record->objecttype,

            objectid:
                (int)$record->objectid,

            relation:
                (string)$record->relation,

            timecreated:
                (int)$record->timecreated,

            createdby:
                (int)$record->createdby
        );
    }
}