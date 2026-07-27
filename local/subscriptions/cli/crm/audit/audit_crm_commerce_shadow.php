<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\commerce\shadow\CrmCommerceShadowAuditor;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
        'json' => false,
        'limit' => 0,
    ],
    [
        'h' => 'help',
        's' => 'strict',
        'j' => 'json',
        'l' => 'limit',
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
Audit du shadow mode CRM Commerce.

Cette commande compare les snapshots CRM produits par le nouveau domaine
Commerce avec les agrégats issus des tables historiques.

La commande est strictement en lecture seule.

Options :

--strict, -s
    Retourne un code non nul en cas de divergence, fallback ou erreur.

--json, -j
    Retourne le rapport au format JSON.

--limit=0, -l=0
    Limite le nombre d'utilisateurs contrôlés. 0 contrôle tous les clients.

--help, -h
    Affiche cette aide.

Exemples :

php local/subscriptions/cli/crm/audit/audit_crm_commerce_shadow.php

php local/subscriptions/cli/crm/audit/audit_crm_commerce_shadow.php --strict

php local/subscriptions/cli/crm/audit/audit_crm_commerce_shadow.php --json

HELP;

    exit(0);
}

$limit = max(
    0,
    (int)$options['limit']
);

$auditor =
    new CrmCommerceShadowAuditor();

$report = $auditor->audit(
    $limit
);

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
        'CampusFR CRM Commerce shadow audit'
    );

    echo PHP_EOL;

    echo sprintf(
        'Utilisateurs contrôlés : %d',
        $report->get_users_checked()
    ) . PHP_EOL;

    echo sprintf(
        'Snapshots équivalents  : %d',
        $report->get_equivalent_users()
    ) . PHP_EOL;

    echo sprintf(
        'Snapshots différents   : %d',
        $report->get_different_users()
    ) . PHP_EOL;

    echo sprintf(
        'Fallbacks utilisés     : %d',
        $report->get_fallback_count()
    ) . PHP_EOL;

    echo sprintf(
        'Échecs techniques      : %d',
        $report->get_failure_count()
    ) . PHP_EOL;

    echo PHP_EOL;

    if (!$report->has_problems()) {
        cli_writeln(
            '[OK] Tous les snapshots CRM Commerce sont équivalents aux données Legacy.'
        );
    } else {
        cli_writeln(
            '[WARNING] Des divergences ou fallbacks ont été détectés.'
        );

        foreach ($report->get_details() as $detail) {
            echo json_encode(
                $detail,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) . PHP_EOL;
        }
    }
}

if (
    !empty($options['strict'])
    && $report->has_problems()
) {
    exit(1);
}

exit(0);