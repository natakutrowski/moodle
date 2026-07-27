<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\payment\audit\PaymentConsistencyAuditor;

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
    $unrecognized = implode(
        PHP_EOL . '  ',
        $unrecognized
    );

    cli_error(
        'Options inconnues :' .
        PHP_EOL .
        '  ' .
        $unrecognized
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Audit de cohérence des paiements CampusFR.

Options :

--strict, -s
    Retourne un code de sortie non nul si une anomalie est détectée.

--json, -j
    Retourne le rapport au format JSON.

--help, -h
    Affiche cette aide.

Exemples :

php local/subscriptions/cli/commerce/operations/validate_payment_consistency.php

php local/subscriptions/cli/commerce/operations/validate_payment_consistency.php --strict

php local/subscriptions/cli/commerce/operations/validate_payment_consistency.php --json

HELP;

    exit(0);
}

$auditor = new PaymentConsistencyAuditor();
$report = $auditor->audit();

if (!empty($options['json'])) {
    echo json_encode(
        [
            'counters' => $report->counters(),
            'issues' => $report->issues(),
            'summary' => [
                'issues' =>
                    $report->issue_count(),

                'warnings' =>
                    $report->count_by_severity(
                        'warning'
                    ),

                'errors' =>
                    $report->count_by_severity(
                        'error'
                    ),
            ],
        ],
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    echo PHP_EOL;
} else {
    cli_heading(
        'CampusFR payment consistency audit'
    );

    echo PHP_EOL;
    echo 'Compteurs :' . PHP_EOL;

    foreach (
        $report->counters()
        as $key => $value
    ) {
        echo sprintf(
            '  %-45s %d',
            $key,
            $value
        ) . PHP_EOL;
    }

    echo PHP_EOL;
    echo 'Anomalies :' . PHP_EOL;

    if (!$report->has_issues()) {
        cli_writeln(
            '[OK] Aucune anomalie détectée.'
        );
    } else {
        foreach ($report->issues() as $issue) {
            $severity = strtoupper(
                (string)$issue['severity']
            );

            echo sprintf(
                '[%s] %s — %s',
                $severity,
                $issue['code'],
                $issue['message']
            ) . PHP_EOL;

            if (!empty($issue['context'])) {
                echo '       ' .
                    json_encode(
                        $issue['context'],
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    ) .
                    PHP_EOL;
            }
        }
    }

    echo PHP_EOL;

    echo sprintf(
        'Résumé : %d anomalie(s), %d avertissement(s), %d erreur(s).',
        $report->issue_count(),
        $report->count_by_severity(
            'warning'
        ),
        $report->count_by_severity(
            'error'
        )
    ) . PHP_EOL;
}

if (
    !empty($options['strict']) &&
    $report->has_issues()
) {
    exit(1);
}

exit(0);