<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\success\plans\domain\CustomerSuccessActionCategory;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanBuilder;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanCreationService;
use local_subscriptions\crm\success\plans\services\CustomerSuccessRecommendationInputFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'userid' => 0,
        'persist' => false,
        'actorid' => 0,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Test Customer Success plan priority, dependencies and persistence.

Options:
--userid=N      Moodle user ID used by the generated plan.
--persist       Persist the generated draft.
--actorid=N     Actor ID used for persistence. Defaults to admin.
-h, --help      Display this help.

Example:
php local/subscriptions/cli/crm/success/test_crm_success_plan_builder.php --userid=23
php local/subscriptions/cli/crm/success/test_crm_success_plan_builder.php --userid=23 --persist

HELP;

    exit(0);
}

$userid = (int)$options['userid'];

if ($userid <= 0) {
    cli_error(
        '--userid must be greater than zero.'
    );
}

if (
    !$DB->record_exists(
        'user',
        ['id' => $userid]
    )
) {
    cli_error(
        'User not found.'
    );
}

$actorid = (int)$options['actorid'];

if ($actorid <= 0) {
    $actorid = (int)get_admin()->id;
}

$factory =
    new CustomerSuccessRecommendationInputFactory();

$recommendations = $factory->from_arrays([
    [
        'recommendationid' => 900001,
        'userid' => $userid,
        'recommendationkey' =>
            'payment_follow_up',
        'title' =>
            'Vérifier le paiement en attente',
        'description' =>
            'Confirmer le statut du paiement avant de rétablir les accès.',
        'category' =>
            CustomerSuccessActionCategory::PAYMENT,
        'priority' => 'urgent',
        'actionkey' =>
            'verify_payment',
        'impactscore' => 95,
        'urgencyscore' => 95,
        'valuescore' => 90,
        'effortscore' => 25,
    ],
    [
        'recommendationid' => 900002,
        'userid' => $userid,
        'recommendationkey' =>
            'restore_access',
        'title' =>
            'Rétablir l’accès à la formation',
        'description' =>
            'Vérifier les droits d’accès après résolution du paiement.',
        'category' =>
            CustomerSuccessActionCategory::ACCESS,
        'priority' => 'high',
        'actionkey' =>
            'restore_course_access',
        'impactscore' => 90,
        'urgencyscore' => 80,
        'valuescore' => 90,
        'effortscore' => 30,
    ],
    [
        'recommendationid' => 900003,
        'userid' => $userid,
        'recommendationkey' =>
            'learning_follow_up',
        'title' =>
            'Relancer la progression pédagogique',
        'description' =>
            'Proposer une reprise progressive après le rétablissement de l’accès.',
        'category' =>
            CustomerSuccessActionCategory::LEARNING,
        'priority' => 'normal',
        'actionkey' =>
            'prepare_learning_follow_up',
        'impactscore' => 70,
        'urgencyscore' => 55,
        'valuescore' => 75,
        'effortscore' => 45,
    ],
]);

$builder =
    new CustomerSuccessPlanBuilder();

$draft = $builder->build(
    $userid,
    $recommendations
);

echo 'Objective: ' .
    $draft->objectivekey .
    PHP_EOL;

echo 'Title: ' .
    $draft->title .
    PHP_EOL;

echo 'Priority: ' .
    $draft->priority .
    PHP_EOL;

echo 'Fingerprint: ' .
    $draft->fingerprint .
    PHP_EOL;

echo 'Steps:' . PHP_EOL;

foreach ($draft->steps as $step) {
    echo sprintf(
        "%d. %s [%s] score=%.2f recommendation=%d depends_on=%s blocked=%s\n",
        $step->position,
        $step->title,
        $step->priority,
        $step->priorityscore,
        $step->recommendationid,
        $step->dependsonrecommendationid !== null
            ? (string)$step->dependsonrecommendationid
            : '-',
        $step->blockedreason ?? '-'
    );
}

if (empty($options['persist'])) {
    exit(0);
}

$result =
    (new CustomerSuccessPlanCreationService())
        ->create_from_recommendations(
            userid:
                $userid,
            recommendations:
                $recommendations,
            actorid:
                $actorid
        );

echo 'Plan ID: ' .
    $result->planid .
    PHP_EOL;

echo 'Created: ' .
    ($result->created ? 'yes' : 'no') .
    PHP_EOL;

echo 'Duplicate: ' .
    ($result->duplicate ? 'yes' : 'no') .
    PHP_EOL;

echo 'Step count: ' .
    $result->stepcount .
    PHP_EOL;