<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\CommerceCompatibilityAuditor;
use local_subscriptions\commerce\audit\CommerceCompatibilityIssue;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
        'json' => false,
        'batch-size' => 200,
    ],
    [
        'h' => 'help',
        's' => 'strict',
        'j' => 'json',
        'b' => 'batch-size',
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
Audit de compatibilité Legacy → Commerce.

Cette commande vérifie que les abonnements et achats digitaux historiques
peuvent être représentés par le nouveau domaine Commerce.

La commande est strictement en lecture seule.

Options :

--strict, -s
    Retourne un code de sortie non nul si une erreur est détectée.

--json, -j
    Retourne le rapport au format JSON.

--batch-size=200, -b=200
    Nombre d'enregistrements traités par lot.

--help, -h
    Affiche cette aide.

Exemples :

php local/subscriptions/cli/audit_commerce_compatibility.php

php local/subscriptions/cli/audit_commerce_compatibility.php --strict

php local/subscriptions/cli/audit_commerce_compatibility.php --json

HELP;

    exit(0);
}

$batchsize = max(
    1,
    min(
        1000,
        (int)$options['batch-size']
    )
);

$auditor = new CommerceCompatibilityAuditor();
$report = $auditor->audit($batchsize);

if (!empty($options['json'])) {
    echo json_encode(
        $report->to_array(),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    echo PHP_EOL;
} else {
    cli_heading(
        'CampusFR Commerce compatibility audit'
    );

    echo PHP_EOL;
    echo 'Compteurs :' . PHP_EOL;

    foreach (
        $report->get_counters()
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
            '[OK] Toutes les données historiques peuvent être représentées par le domaine Commerce.'
        );
    } else {
        foreach ($report->get_issues() as $issue) {
            echo sprintf(
                '[%s] %s — %s',
                strtoupper($issue->get_severity()),
                $issue->get_code(),
                $issue->get_message()
            ) . PHP_EOL;

            if ($issue->get_context() !== []) {
                echo '       ' .
                    json_encode(
                        $issue->get_context(),
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
        $report->get_issue_count(),
        $report->count_by_severity(
            CommerceCompatibilityIssue::SEVERITY_WARNING
        ),
        $report->count_by_severity(
            CommerceCompatibilityIssue::SEVERITY_ERROR
        )
    ) . PHP_EOL;
}

if (
    !empty($options['strict'])
    && $report->has_errors()
) {
    exit(1);
}

exit(0);