<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\validation\PaymentReleaseValidator;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
        'json' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
        'j' => 'json',
    ]
);

if ($unrecognized) {
    cli_error(
        'Options inconnues :' .
        PHP_EOL .
        '  ' .
        implode(
            PHP_EOL . '  ',
            $unrecognized
        )
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Validation pré-PROD des paiements CampusFR.

Ce script ne modifie aucune donnée.

Options :

--strict, -s
    Retourne un code de sortie non nul si une erreur est détectée.

--json, -j
    Affiche le résultat au format JSON.

--help, -h
    Affiche cette aide.

Exemples :

php local/subscriptions/cli/commerce/operations/validate_payment_release.php

php local/subscriptions/cli/commerce/operations/validate_payment_release.php --strict

php local/subscriptions/cli/commerce/operations/validate_payment_release.php --json

HELP;

    exit(0);
}

$validator =
    new PaymentReleaseValidator();

$result =
    $validator->validate();

if (!empty($options['json'])) {
    echo json_encode(
        [
            'status' =>
                $result->release_status(),

            'summary' =>
                $result->summary(),

            'checks' =>
                $result->checks(),
        ],
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    echo PHP_EOL;
} else {
    cli_heading(
        'CampusFR payment release validation'
    );

    echo PHP_EOL;

    foreach ($result->checks() as $check) {
        $severity =
            strtoupper(
                (string)$check['severity']
            );

        echo sprintf(
            '[%s] %s — %s',
            $severity,
            $check['code'],
            $check['message']
        ) . PHP_EOL;

        if (!empty($check['context'])) {
            echo '       ' .
                json_encode(
                    $check['context'],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ) .
                PHP_EOL;
        }
    }

    $summary =
        $result->summary();

    echo PHP_EOL;

    echo 'Statut de release : ' . $result->release_status() . PHP_EOL;

    echo sprintf(
        'Résumé : %d OK, %d avertissement(s), %d erreur(s), %d contrôle(s).',
        $summary['ok'],
        $summary['warnings'],
        $summary['errors'],
        $summary['total']
    ) . PHP_EOL;
}

if (
    !empty($options['strict']) &&
    $result->has_errors()
) {
    exit(1);
}

exit(0);