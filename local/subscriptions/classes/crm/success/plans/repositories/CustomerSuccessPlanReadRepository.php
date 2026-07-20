<?php

namespace local_subscriptions\crm\success\plans\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlan;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;

/**
 * Read-only repository for Customer Success plans.
 */
final class CustomerSuccessPlanReadRepository {

    private const PLAN_TABLE =
        'local_subscriptions_cs_plan';

    private const STEP_TABLE =
        'local_subscriptions_cs_step';

    public function get(
        int $planid
    ): CustomerSuccessPlan {
        global $DB;

        $record = $DB->get_record(
            self::PLAN_TABLE,
            [
                'id' => $planid,
            ],
            '*',
            MUST_EXIST
        );

        return $this->map_plan(
            $record,
            $this->get_steps($planid)
        );
    }

    /**
     * @return CustomerSuccessPlan[]
     */
    public function get_for_user(
        int $userid,
        bool $openonly = false
    ): array {
        global $DB;

        $conditions = [
            'userid = :userid',
        ];

        $params = [
            'userid' => $userid,
        ];

        if ($openonly) {
            [$insql, $inparams] =
                $DB->get_in_or_equal(
                    CustomerSuccessPlanStatus::open(),
                    SQL_PARAMS_NAMED,
                    'csplanstatus'
                );

            $conditions[] =
                "status {$insql}";

            $params += $inparams;
        }

        $records = $DB->get_records_select(
            self::PLAN_TABLE,
            implode(
                ' AND ',
                $conditions
            ),
            $params,
            'timecreated DESC, id DESC'
        );

        if ($records === []) {
            return [];
        }

        $stepsbyplan =
            $this->get_steps_for_plans(
                array_map(
                    'intval',
                    array_keys($records)
                )
            );

        $plans = [];

        foreach ($records as $record) {
            $planid = (int)$record->id;

            $plans[] = $this->map_plan(
                $record,
                $stepsbyplan[$planid] ?? []
            );
        }

        return $plans;
    }

    /**
     * Returns the highest-priority open Customer Success plan
     * for each requested user.
     *
     * This summary query intentionally does not load plan steps.
     *
     * @param int[] $userids
     * @return array<int,\stdClass>
     */
    public function get_primary_open_for_users(
        array $userids
    ): array {
        global $DB;

        $userids = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $userids
                    ),
                    static fn(int $userid): bool =>
                        $userid > 0
                )
            )
        );

        if ($userids === []) {
            return [];
        }

        [
            $usersql,
            $userparams,
        ] = $DB->get_in_or_equal(
            $userids,
            SQL_PARAMS_NAMED,
            'alertcsuser'
        );

        [
            $statussql,
            $statusparams,
        ] = $DB->get_in_or_equal(
            CustomerSuccessPlanStatus::open(),
            SQL_PARAMS_NAMED,
            'alertcsstatus'
        );

        $records = $DB->get_records_sql(
            "SELECT plan.*,
                    assignee.firstname
                        AS assigneefirstname,
                    assignee.lastname
                        AS assigneelastname,
                    assignee.firstnamephonetic
                        AS assigneefirstnamephonetic,
                    assignee.lastnamephonetic
                        AS assigneelastnamephonetic,
                    assignee.middlename
                        AS assigneemiddlename,
                    assignee.alternatename
                        AS assigneealternatename,
                    team.name AS teamname

            FROM {" . self::PLAN_TABLE . "} plan

        LEFT JOIN {user} assignee
                ON assignee.id =
                    plan.assigneduserid

        LEFT JOIN {local_subscriptions_work_team} team
                ON team.id =
                    plan.assignedteamid

            WHERE plan.userid {$usersql}
                AND plan.status {$statussql}

        ORDER BY plan.userid ASC,

                    CASE plan.priority
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'normal' THEN 4
                        WHEN 'low' THEN 5
                        ELSE 6
                    END ASC,

                    CASE
                        WHEN plan.targetdate IS NULL
                        OR plan.targetdate = 0
                        THEN 1
                        ELSE 0
                    END ASC,

                    plan.targetdate ASC,
                    plan.timemodified DESC,
                    plan.id DESC",
            $userparams + $statusparams
        );

        $result = [];

        foreach ($records as $record) {
            $userid = (int)$record->userid;

            if (
                $userid <= 0 ||
                isset($result[$userid])
            ) {
                continue;
            }

            $result[$userid] = $record;
        }

        return $result;
    }

    public function find_open_by_fingerprint(
        string $fingerprint
    ): ?CustomerSuccessPlan {
        global $DB;

        $fingerprint = trim(
            $fingerprint
        );

        if ($fingerprint === '') {
            return null;
        }

        [$insql, $params] =
            $DB->get_in_or_equal(
                CustomerSuccessPlanStatus::open(),
                SQL_PARAMS_NAMED,
                'csfingerprintstatus'
            );

        $params['fingerprint'] =
            $fingerprint;

        $records = $DB->get_records_select(
            self::PLAN_TABLE,
            "fingerprint = :fingerprint
             AND status {$insql}",
            $params,
            'timecreated DESC, id DESC',
            '*',
            0,
            1
        );

        if ($records === []) {
            return null;
        }

        $record = reset($records);

        return $record
            ? $this->map_plan(
                $record,
                $this->get_steps(
                    (int)$record->id
                )
            )
            : null;
    }

    /**
     * @return CustomerSuccessPlanStep[]
     */
    public function get_steps(
        int $planid
    ): array {
        global $DB;

        $records = $DB->get_records(
            self::STEP_TABLE,
            [
                'planid' => $planid,
            ],
            'position ASC, id ASC'
        );

        return array_map(
            fn(\stdClass $record):
                CustomerSuccessPlanStep =>
                    $this->map_step(
                        $record
                    ),
            array_values($records)
        );
    }

    public function get_step(
        int $stepid
    ): CustomerSuccessPlanStep {
        global $DB;

        $record = $DB->get_record(
            self::STEP_TABLE,
            [
                'id' => $stepid,
            ],
            '*',
            MUST_EXIST
        );

        return $this->map_step(
            $record
        );
    }

    /**
     * @param int[] $planids
     * @return array<int,CustomerSuccessPlanStep[]>
     */
    private function get_steps_for_plans(
        array $planids
    ): array {
        global $DB;

        if ($planids === []) {
            return [];
        }

        [$insql, $params] =
            $DB->get_in_or_equal(
                $planids,
                SQL_PARAMS_NAMED,
                'csstepplan'
            );

        $records = $DB->get_records_select(
            self::STEP_TABLE,
            "planid {$insql}",
            $params,
            'planid ASC, position ASC, id ASC'
        );

        $result = [];

        foreach ($records as $record) {
            $planid = (int)$record->planid;

            $result[$planid][] =
                $this->map_step(
                    $record
                );
        }

        return $result;
    }

    /**
     * @param CustomerSuccessPlanStep[] $steps
     */
    private function map_plan(
        \stdClass $record,
        array $steps
    ): CustomerSuccessPlan {
        return new CustomerSuccessPlan(
            id: (int)$record->id,
            reference:
                (string)$record->reference,
            userid: (int)$record->userid,
            objectivekey:
                (string)$record->objectivekey,
            title: (string)$record->title,
            description:
                $this->nullable_string(
                    $record->description
                ),
            status: (string)$record->status,
            source: (string)$record->source,
            priority:
                (string)$record->priority,
            assignedteamid:
                $this->nullable_int(
                    $record->assignedteamid
                ),
            assigneduserid:
                $this->nullable_int(
                    $record->assigneduserid
                ),
            targetdate:
                $this->nullable_int(
                    $record->targetdate
                ),
            blockedreason:
                $this->nullable_string(
                    $record->blockedreason
                ),
            fingerprint:
                $this->nullable_string(
                    $record->fingerprint
                ),
            activatedat:
                $this->nullable_int(
                    $record->activatedat
                ),
            completedat:
                $this->nullable_int(
                    $record->completedat
                ),
            timecreated:
                (int)$record->timecreated,
            timemodified:
                (int)$record->timemodified,
            createdby:
                (int)$record->createdby,
            modifiedby:
                (int)$record->modifiedby,
            steps: $steps
        );
    }

    private function map_step(
        \stdClass $record
    ): CustomerSuccessPlanStep {
        return new CustomerSuccessPlanStep(
            id: (int)$record->id,
            planid: (int)$record->planid,
            position:
                (int)$record->position,
            stepkey:
                (string)$record->stepkey,
            title: (string)$record->title,
            description:
                $this->nullable_string(
                    $record->description
                ),
            status:
                (string)$record->status,
            priority:
                (string)$record->priority,
            dependsonstepid:
                $this->nullable_int(
                    $record->dependsonstepid
                ),
            blockedreason:
                $this->nullable_string(
                    $record->blockedreason
                ),
            relationtype:
                $this->nullable_string(
                    $record->relationtype
                ),
            relationid:
                $this->nullable_int(
                    $record->relationid
                ),
            assignedteamid:
                $this->nullable_int(
                    $record->assignedteamid
                ),
            assigneduserid:
                $this->nullable_int(
                    $record->assigneduserid
                ),
            dueat:
                $this->nullable_int(
                    $record->dueat
                ),
            startedat:
                $this->nullable_int(
                    $record->startedat
                ),
            completedat:
                $this->nullable_int(
                    $record->completedat
                ),
            timecreated:
                (int)$record->timecreated,
            timemodified:
                (int)$record->timemodified,
            createdby:
                (int)$record->createdby,
            modifiedby:
                (int)$record->modifiedby
        );
    }

    private function nullable_int(
        mixed $value
    ): ?int {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        $value = (int)$value;

        return $value > 0
            ? $value
            : null;
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string)$value
        );

        return $value !== ''
            ? $value
            : null;
    }
}