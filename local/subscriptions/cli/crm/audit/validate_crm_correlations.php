<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\intelligence\recommendations\RecommendationEngine;
use local_subscriptions\crm\intelligence\recommendations\correlation\CorrelationConfidence;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\ChurnRiskCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\DisengagementSpiralCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\LearningSupportPressureCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\OperationalOverloadCorrelationRule;
use local_subscriptions\crm\intelligence\recommendations\correlation\rules\PaymentSupportFrictionCorrelationRule;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
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
Validate the CRM Correlation Engine.

Options:
--strict       Return an error if a validation warning is detected.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/crm/audit/validate_crm_correlations.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$expectedrules = [
    ChurnRiskCorrelationRule::KEY,
    PaymentSupportFrictionCorrelationRule::KEY,
    LearningSupportPressureCorrelationRule::KEY,
    OperationalOverloadCorrelationRule::KEY,
    DisengagementSpiralCorrelationRule::KEY,
];

$engine = new RecommendationEngine();

$registeredrules =
    $engine->correlation_rule_keys();

echo '[OK] Recommendation Engine available.' .
    PHP_EOL;

if ($registeredrules === []) {
    $errors[] =
        'No correlation rule is registered.';
} else {
    echo '[OK] ' .
        count($registeredrules) .
        ' correlation rules registered.' .
        PHP_EOL;
}

foreach ($expectedrules as $rulekey) {
    if (
        !in_array(
            $rulekey,
            $registeredrules,
            true
        )
    ) {
        $errors[] =
            'Missing correlation rule: ' .
            $rulekey;
        continue;
    }

    echo '[OK] Correlation rule registered: ' .
        $rulekey .
        PHP_EOL;
}

if (
    count($registeredrules) !==
    count(array_unique($registeredrules))
) {
    $errors[] =
        'Duplicate correlation rule keys detected.';
} else {
    echo '[OK] Correlation rule keys are unique.' .
        PHP_EOL;
}

foreach ([0, 54, 55, 74, 75, 89, 90, 100] as $score) {
    $level =
        CorrelationConfidence::from_score(
            $score
        );

    if (
        !CorrelationConfidence::is_valid(
            $level
        )
    ) {
        $errors[] =
            'Invalid confidence level for score ' .
            $score;
    }
}

if ($errors === []) {
    echo '[OK] Correlation confidence thresholds validated.' .
        PHP_EOL;
}

foreach ($warnings as $warning) {
    echo '[WARNING] ' .
        $warning .
        PHP_EOL;
}

foreach ($errors as $error) {
    echo '[ERROR] ' .
        $error .
        PHP_EOL;
}

if (
    $errors !== [] ||
    (
        !empty($options['strict']) &&
        $warnings !== []
    )
) {
    exit(1);
}

echo '[OK] CRM Correlation Engine validation completed.' .
    PHP_EOL;

exit(0);