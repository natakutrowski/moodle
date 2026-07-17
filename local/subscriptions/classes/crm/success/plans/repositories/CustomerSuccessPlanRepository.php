<?php

namespace local_subscriptions\crm\success\plans\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanDraft;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;

/**
 * Persistence repository for Customer Success plans and steps.
 *
 * This repository performs database writes only.
 * Lifecycle validation will be handled by services introduced in 7.8D.
 */
final class CustomerSuccessPlanRepository {

    private const PLAN_TABLE =
        'local_subscriptions_cs_plan';

    private const STEP_TABLE =
        'local_subscriptions_cs_step';

    public function __construct(
        private readonly CustomerSuccessPlanRelationRepository $relations =
            new CustomerSuccessPlanRelationRepository()
    ) {
    }

    public function create_plan(
        int $userid,
        string $objectivekey,
        string $title,
        ?string $description,
        string $source,
        string $priority,
        int $actorid,
        ?int $assignedteamid = null,
        ?int $assigneduserid = null,
        ?int $targetdate = null,
        ?string $fingerprint = null
    ): int {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan userid must be greater than zero.'
            );
        }

        if ($actorid < 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan actor ID cannot be negative.'
            );
        }

        $objectivekey =
            $this->normalize_key(
                $objectivekey
            );

        $title = trim($title);

        if ($title === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan title is required.'
            );
        }

        if (
            !CustomerSuccessPlanSource::is_valid(
                $source
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan source.'
            );
        }

        $now = time();

        return (int)$DB->insert_record(
            self::PLAN_TABLE,
            (object)[
                'reference' =>
                    $this->generate_reference(),
                'userid' => $userid,
                'objectivekey' =>
                    $objectivekey,
                'title' => $title,
                'description' =>
                    $this->nullable_text(
                        $description
                    ),
                'status' =>
                    CustomerSuccessPlanStatus::DRAFT,
                'source' => $source,
                'priority' =>
                    $this->normalize_priority(
                        $priority
                    ),
                'assignedteamid' =>
                    $this->nullable_id(
                        $assignedteamid
                    ),
                'assigneduserid' =>
                    $this->nullable_id(
                        $assigneduserid
                    ),
                'targetdate' =>
                    $this->nullable_timestamp(
                        $targetdate
                    ),
                'blockedreason' => null,
                'fingerprint' =>
                    $this->normalize_fingerprint(
                        $fingerprint
                    ),
                'activatedat' => null,
                'completedat' => null,
                'createdby' => $actorid,
                'modifiedby' => $actorid,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function create_step(
        int $planid,
        int $position,
        string $stepkey,
        string $title,
        ?string $description,
        string $priority,
        int $actorid,
        ?int $dependsonstepid = null,
        ?string $relationtype = null,
        ?int $relationid = null,
        ?int $assignedteamid = null,
        ?int $assigneduserid = null,
        ?int $dueat = null
    ): int {
        global $DB;

        if ($planid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan ID must be greater than zero.'
            );
        }

        if ($position <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan step position must be greater than zero.'
            );
        }

        $stepkey =
            $this->normalize_key(
                $stepkey
            );

        $title = trim($title);

        if ($title === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan step title is required.'
            );
        }

        if (
            $relationtype === null &&
            $relationid !== null
        ) {
            throw new \InvalidArgumentException(
                'A relation ID requires a relation type.'
            );
        }

        if (
            $relationtype !== null &&
            (
                $relationid === null ||
                $relationid <= 0
            )
        ) {
            throw new \InvalidArgumentException(
                'A relation type requires a valid relation ID.'
            );
        }

        if (
            $relationtype !== null &&
            !CustomerSuccessPlanRelationType::is_valid(
                $relationtype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan relation type.'
            );
        }

        $now = time();

        return (int)$DB->insert_record(
            self::STEP_TABLE,
            (object)[
                'planid' => $planid,
                'position' => $position,
                'stepkey' => $stepkey,
                'title' => $title,
                'description' =>
                    $this->nullable_text(
                        $description
                    ),
                'status' =>
                    $dependsonstepid === null
                        ? CustomerSuccessPlanStepStatus::READY
                        : CustomerSuccessPlanStepStatus::PENDING,
                'priority' =>
                    $this->normalize_priority(
                        $priority
                    ),
                'dependsonstepid' =>
                    $this->nullable_id(
                        $dependsonstepid
                    ),
                'blockedreason' => null,
                'relationtype' =>
                    $relationtype,
                'relationid' =>
                    $this->nullable_id(
                        $relationid
                    ),
                'assignedteamid' =>
                    $this->nullable_id(
                        $assignedteamid
                    ),
                'assigneduserid' =>
                    $this->nullable_id(
                        $assigneduserid
                    ),
                'dueat' =>
                    $this->nullable_timestamp(
                        $dueat
                    ),
                'startedat' => null,
                'completedat' => null,
                'createdby' => $actorid,
                'modifiedby' => $actorid,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function update_plan(
        int $planid,
        array $fields,
        int $actorid
    ): void {
        global $DB;

        if ($planid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan ID must be greater than zero.'
            );
        }

        $allowed = [
            'title',
            'description',
            'status',
            'priority',
            'assignedteamid',
            'assigneduserid',
            'targetdate',
            'blockedreason',
            'fingerprint',
            'activatedat',
            'completedat',
        ];

        $record = (object)[
            'id' => $planid,
            'modifiedby' => $actorid,
            'timemodified' => time(),
        ];

        foreach ($allowed as $fieldname) {
            if (
                array_key_exists(
                    $fieldname,
                    $fields
                )
            ) {
                $record->{$fieldname} =
                    $fields[$fieldname];
            }
        }

        $DB->update_record(
            self::PLAN_TABLE,
            $record
        );
    }

    public function update_step(
        int $stepid,
        array $fields,
        int $actorid
    ): void {
        global $DB;

        if ($stepid <= 0) {
            throw new \InvalidArgumentException(
                'Customer Success plan step ID must be greater than zero.'
            );
        }

        $allowed = [
            'position',
            'title',
            'description',
            'status',
            'priority',
            'dependsonstepid',
            'blockedreason',
            'relationtype',
            'relationid',
            'assignedteamid',
            'assigneduserid',
            'dueat',
            'startedat',
            'completedat',
        ];

        $record = (object)[
            'id' => $stepid,
            'modifiedby' => $actorid,
            'timemodified' => time(),
        ];

        foreach ($allowed as $fieldname) {
            if (
                array_key_exists(
                    $fieldname,
                    $fields
                )
            ) {
                $record->{$fieldname} =
                    $fields[$fieldname];
            }
        }

        $DB->update_record(
            self::STEP_TABLE,
            $record
        );
    }

    public function delete_step(
        int $stepid
    ): void {
        global $DB;

        $DB->delete_records(
            self::STEP_TABLE,
            [
                'id' => $stepid,
            ]
        );
    }

    public function delete_plan(
        int $planid
    ): void {
        global $DB;

        $transaction =
            $DB->start_delegated_transaction();

        $DB->delete_records(
            self::STEP_TABLE,
            [
                'planid' => $planid,
            ]
        );

        $DB->delete_records(
            self::PLAN_TABLE,
            [
                'id' => $planid,
            ]
        );

        $transaction->allow_commit();
    }

    /**
     * Persists one complete plan draft atomically.
     *
     * @return array{planid:int,stepids:array<int,int>}
     */
    public function create_from_draft(
        CustomerSuccessPlanDraft $draft,
        int $actorid
    ): array {
        global $DB;

        $transaction =
            $DB->start_delegated_transaction();

        try {
            $planid = $this->create_plan(
                userid:
                    $draft->userid,
                objectivekey:
                    $draft->objectivekey,
                title:
                    $draft->title,
                description:
                    $draft->description,
                source:
                    $draft->source,
                priority:
                    $draft->priority,
                actorid:
                    $actorid,
                fingerprint:
                    $draft->fingerprint
            );

            /*
            * First pass:
            * persist steps without database dependency IDs.
            */
            $stepidsbyrecommendation = [];

            foreach ($draft->steps as $step) {
                $stepid = $this->create_step(
                    planid: $planid,
                    position:
                        $step->position,
                    stepkey:
                        $step->stepkey,
                    title:
                        $step->title,
                    description:
                        $step->description,
                    priority:
                        $step->priority,
                    actorid:
                        $actorid,
                    relationtype:
                        CustomerSuccessPlanRelationType::
                            RECOMMENDATION,
                    relationid:
                        $step->recommendationid
                );

                $this->relations->add(
                    planid: $planid,
                    stepid: $stepid,
                    objecttype:
                        CustomerSuccessPlanRelationType::RECOMMENDATION,
                    objectid:
                        $step->recommendationid,
                    relation:
                        CustomerSuccessPlanRelation::CREATED_FROM,
                    actorid:
                        $actorid
                );

                $stepidsbyrecommendation[
                    $step->recommendationid
                ] = $stepid;

                if ($step->blockedreason !== null) {
                    $this->update_step(
                        $stepid,
                        [
                            'status' =>
                                CustomerSuccessPlanStepStatus::
                                    BLOCKED,
                            'blockedreason' =>
                                $step->blockedreason,
                        ],
                        $actorid
                    );
                }
            }

            /*
            * Second pass:
            * translate recommendation dependencies to persisted step IDs.
            */
            foreach ($draft->steps as $step) {
                if (
                    $step->dependsonrecommendationid ===
                    null
                ) {
                    continue;
                }

                $stepid =
                    $stepidsbyrecommendation[
                        $step->recommendationid
                    ] ?? null;

                $dependsonstepid =
                    $stepidsbyrecommendation[
                        $step->dependsonrecommendationid
                    ] ?? null;

                if (
                    $stepid === null ||
                    $dependsonstepid === null
                ) {
                    throw new \coding_exception(
                        'Customer Success plan dependency could not be persisted.'
                    );
                }

                $this->update_step(
                    $stepid,
                    [
                        'dependsonstepid' =>
                            $dependsonstepid,
                        'status' =>
                            CustomerSuccessPlanStepStatus::
                                PENDING,
                    ],
                    $actorid
                );
            }

            $transaction->allow_commit();

            return [
                'planid' => $planid,
                'stepids' =>
                    $stepidsbyrecommendation,
            ];
        } catch (\Throwable $exception) {
            $transaction->rollback(
                $exception
            );

            throw $exception;
        }
    }

    private function generate_reference(): string {
        return 'CSP-' .
            strtoupper(
                bin2hex(
                    random_bytes(8)
                )
            );
    }

    private function normalize_key(
        string $value
    ): string {
        $value =
            \core_text::strtolower(
                trim($value)
            );

        $value = preg_replace(
            '/[^a-z0-9_.-]+/',
            '_',
            $value
        ) ?? '';

        $value = trim(
            $value,
            '_'
        );

        if ($value === '') {
            throw new \InvalidArgumentException(
                'Customer Success plan key is invalid.'
            );
        }

        return \core_text::substr(
            $value,
            0,
            100
        );
    }

    private function normalize_priority(
        string $priority
    ): string {
        $priority =
            \core_text::strtolower(
                trim($priority)
            );

        if (
            !in_array(
                $priority,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'critical',
                ],
                true
            )
        ) {
            return 'normal';
        }

        return $priority;
    }

    private function normalize_fingerprint(
        ?string $fingerprint
    ): ?string {
        if ($fingerprint === null) {
            return null;
        }

        $fingerprint = trim(
            $fingerprint
        );

        if ($fingerprint === '') {
            return null;
        }

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $fingerprint
            ) === 1
        ) {
            return $fingerprint;
        }

        return hash(
            'sha256',
            $fingerprint
        );
    }

    private function nullable_id(
        ?int $value
    ): ?int {
        return $value !== null &&
            $value > 0
                ? $value
                : null;
    }

    private function nullable_timestamp(
        ?int $value
    ): ?int {
        return $value !== null &&
            $value > 0
                ? $value
                : null;
    }

    private function nullable_text(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}