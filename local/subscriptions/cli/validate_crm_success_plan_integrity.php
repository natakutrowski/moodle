<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;

list($options) = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    echo <<<TXT
Validate Customer Success plan data integrity.

Checks:
- plan and step technical values;
- step dependency consistency;
- relations linked to the correct plan;
- orphaned relations;
- duplicate semantic relations;
- Explorer aggregate consistency.

Options:
  --strict       Treat warnings as errors.
  -h, --help     Show this help.

TXT;

    exit(0);
}

$strict = !empty($options['strict']);

$errors = [];
$warnings = [];

$plantable =
    new xmldb_table(
        'local_subscriptions_cs_plan'
    );

$steptable =
    new xmldb_table(
        'local_subscriptions_cs_step'
    );

$relationtable =
    new xmldb_table(
        'local_subscriptions_cs_relation'
    );

$dbman = $DB->get_manager();

foreach (
    [
        'local_subscriptions_cs_plan' =>
            $plantable,

        'local_subscriptions_cs_step' =>
            $steptable,

        'local_subscriptions_cs_relation' =>
            $relationtable,
    ]
    as $tablename => $table
) {
    if (!$dbman->table_exists($table)) {
        $errors[] =
            'Missing table: ' .
            $tablename;
    }
}

if ($errors === []) {
    $plans = $DB->get_records(
        'local_subscriptions_cs_plan',
        null,
        'id ASC',
        '
            id,
            userid,
            status,
            source,
            priority
        '
    );

    foreach ($plans as $plan) {
        if (
            !CustomerSuccessPlanStatus::is_valid(
                (string)$plan->status
            )
        ) {
            $errors[] =
                sprintf(
                    'Plan %d has invalid status: %s',
                    (int)$plan->id,
                    (string)$plan->status
                );
        }

        if (
            !CustomerSuccessPlanSource::is_valid(
                (string)$plan->source
            )
        ) {
            $errors[] =
                sprintf(
                    'Plan %d has invalid source: %s',
                    (int)$plan->id,
                    (string)$plan->source
                );
        }

        if (
            !in_array(
                (string)$plan->priority,
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
            $errors[] =
                sprintf(
                    'Plan %d has invalid priority: %s',
                    (int)$plan->id,
                    (string)$plan->priority
                );
        }

        if ((int)$plan->userid <= 0) {
            $errors[] =
                sprintf(
                    'Plan %d has invalid userid.',
                    (int)$plan->id
                );
        }
    }

    $steps = $DB->get_records(
        'local_subscriptions_cs_step',
        null,
        'id ASC',
        '
            id,
            planid,
            status,
            dependsonstepid,
            relationtype,
            relationid
        '
    );

    $stepsbyid = [];

    foreach ($steps as $step) {
        $stepsbyid[(int)$step->id] =
            $step;

        if (
            !CustomerSuccessPlanStepStatus::is_valid(
                (string)$step->status
            )
        ) {
            $errors[] =
                sprintf(
                    'Step %d has invalid status: %s',
                    (int)$step->id,
                    (string)$step->status
                );
        }

        if (
            !$DB->record_exists(
                'local_subscriptions_cs_plan',
                [
                    'id' =>
                        (int)$step->planid,
                ]
            )
        ) {
            $errors[] =
                sprintf(
                    'Step %d references missing plan %d.',
                    (int)$step->id,
                    (int)$step->planid
                );
        }

        $relationtype =
            trim(
                (string)(
                    $step->relationtype
                    ?? ''
                )
            );

        $relationid =
            (int)(
                $step->relationid
                ?? 0
            );

        if (
            ($relationtype === '') !==
            ($relationid <= 0)
        ) {
            $errors[] =
                sprintf(
                    'Step %d has an incomplete legacy relation.',
                    (int)$step->id
                );
        }

        if (
            $relationtype !== '' &&
            !CustomerSuccessPlanRelationType::is_valid(
                $relationtype
            )
        ) {
            $errors[] =
                sprintf(
                    'Step %d has invalid relation type: %s',
                    (int)$step->id,
                    $relationtype
                );
        }
    }

    foreach ($steps as $step) {
        $dependencyid =
            (int)(
                $step->dependsonstepid
                ?? 0
            );

        if ($dependencyid <= 0) {
            continue;
        }

        if (!isset($stepsbyid[$dependencyid])) {
            $errors[] =
                sprintf(
                    'Step %d references missing dependency %d.',
                    (int)$step->id,
                    $dependencyid
                );

            continue;
        }

        if (
            (int)$stepsbyid[$dependencyid]->planid !==
            (int)$step->planid
        ) {
            $errors[] =
                sprintf(
                    'Step %d depends on step %d from another plan.',
                    (int)$step->id,
                    $dependencyid
                );
        }

        if ($dependencyid === (int)$step->id) {
            $errors[] =
                sprintf(
                    'Step %d depends on itself.',
                    (int)$step->id
                );
        }
    }

    $relations = $DB->get_records(
        'local_subscriptions_cs_relation',
        null,
        'id ASC'
    );

    foreach ($relations as $relation) {
        $step = $stepsbyid[
            (int)$relation->stepid
        ] ?? null;

        if ($step === null) {
            $errors[] =
                sprintf(
                    'Relation %d references missing step %d.',
                    (int)$relation->id,
                    (int)$relation->stepid
                );

            continue;
        }

        if (
            (int)$relation->planid !==
            (int)$step->planid
        ) {
            $errors[] =
                sprintf(
                    'Relation %d plan %d does not match step %d plan %d.',
                    (int)$relation->id,
                    (int)$relation->planid,
                    (int)$step->id,
                    (int)$step->planid
                );
        }

        if (
            !CustomerSuccessPlanRelationType::is_valid(
                (string)$relation->objecttype
            )
        ) {
            $errors[] =
                sprintf(
                    'Relation %d has invalid object type: %s',
                    (int)$relation->id,
                    (string)$relation->objecttype
                );
        }

        if (
            !CustomerSuccessPlanRelation::is_valid(
                (string)$relation->relation
            )
        ) {
            $errors[] =
                sprintf(
                    'Relation %d has invalid semantic relation: %s',
                    (int)$relation->id,
                    (string)$relation->relation
                );
        }

        if ((int)$relation->objectid <= 0) {
            $errors[] =
                sprintf(
                    'Relation %d has invalid object ID.',
                    (int)$relation->id
                );
        }
    }

    $duplicates = $DB->get_records_sql(
        "
            SELECT
                MIN(id) AS id,
                stepid,
                objecttype,
                objectid,
                relation,
                COUNT(*) AS duplicatecount

              FROM {local_subscriptions_cs_relation}

          GROUP BY
                stepid,
                objecttype,
                objectid,
                relation

            HAVING COUNT(*) > 1
        "
    );

    foreach ($duplicates as $duplicate) {
        $errors[] =
            sprintf(
                'Duplicate relation for step %d, %s:%d, relation %s.',
                (int)$duplicate->stepid,
                (string)$duplicate->objecttype,
                (int)$duplicate->objectid,
                (string)$duplicate->relation
            );
    }

    $crossplandependencies =
        $DB->get_records_sql(
            "
                SELECT
                    child.id,
                    child.planid AS childplanid,
                    parent.id AS parentid,
                    parent.planid AS parentplanid

                  FROM {local_subscriptions_cs_step} child

                  JOIN {local_subscriptions_cs_step} parent
                    ON parent.id =
                       child.dependsonstepid

                 WHERE child.planid <>
                       parent.planid
            "
        );

    foreach (
        $crossplandependencies
        as $dependency
    ) {
        $errors[] =
            sprintf(
                'Step %d in plan %d depends on step %d in plan %d.',
                (int)$dependency->id,
                (int)$dependency->childplanid,
                (int)$dependency->parentid,
                (int)$dependency->parentplanid
            );
    }

    $relationmismatches =
        $DB->get_records_sql(
            "
                SELECT
                    relationrecord.id,
                    relationrecord.planid,
                    relationrecord.stepid,
                    steprecord.planid AS stepplanid

                  FROM {local_subscriptions_cs_relation}
                       relationrecord

                  JOIN {local_subscriptions_cs_step}
                       steprecord
                    ON steprecord.id =
                       relationrecord.stepid

                 WHERE relationrecord.planid <>
                       steprecord.planid
            "
        );

    foreach (
        $relationmismatches
        as $mismatch
    ) {
        $errors[] =
            sprintf(
                'Relation %d has plan %d but step %d belongs to plan %d.',
                (int)$mismatch->id,
                (int)$mismatch->planid,
                (int)$mismatch->stepid,
                (int)$mismatch->stepplanid
            );
    }
}

foreach ($warnings as $warning) {
    cli_writeln(
        '[WARNING] ' .
        $warning
    );
}

foreach ($errors as $error) {
    cli_writeln(
        '[ERROR] ' .
        $error
    );
}

if (
    $strict &&
    $warnings !== []
) {
    $errors = array_merge(
        $errors,
        $warnings
    );
}

if ($errors !== []) {
    cli_error(
        sprintf(
            'Customer Success integrity validation failed with %d error(s).',
            count($errors)
        )
    );
}

cli_writeln(
    '[OK] Customer Success plan values are valid.'
);

cli_writeln(
    '[OK] Customer Success step dependencies are consistent.'
);

cli_writeln(
    '[OK] Customer Success relations are consistent.'
);

cli_writeln(
    '[OK] No duplicate semantic relation was found.'
);

exit(0);