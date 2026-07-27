<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\purchase\CommercePurchaseArchitectureAuditor;

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
        implode(
            PHP_EOL,
            $unrecognized
        )
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Audit de l'architecture Commerce Purchase.

Cette commande contrôle en lecture seule :

- les PurchaseHandlers enregistrés ;
- les plans Subscription ;
- les produits Digital ;
- leur validation et préparation ;
- la préparation d'un bundle mixte ;
- la génération des opérations de fulfillment.

Options :

--strict, -s
    Retourne un code non nul lorsqu'une erreur technique est détectée.

--json, -j
    Affiche le résultat au format JSON.

--limit=0, -l=0
    Limite le nombre de plans et produits contrôlés.
    0 contrôle toutes les lignes.

--help, -h
    Affiche cette aide.

Exemples :

php local/subscriptions/cli/commerce/audit/audit_commerce_purchase_architecture.php

php local/subscriptions/cli/commerce/audit/audit_commerce_purchase_architecture.php --strict

php local/subscriptions/cli/commerce/audit/audit_commerce_purchase_architecture.php --json

HELP;

    exit(0);
}

$auditor =
    new CommercePurchaseArchitectureAuditor();

$report = $auditor->audit(
    max(
        0,
        (int)$options['limit']
    )
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
        'CampusFR Commerce Purchase Architecture audit'
    );

    echo PHP_EOL;
    echo 'Compteurs :' . PHP_EOL;

    foreach ($report->get_counters() as $key => $value) {
        echo sprintf(
            '  %-48s %d',
            $key,
            $value
        ) . PHP_EOL;
    }

    echo PHP_EOL;

    if ($report->get_warnings() !== []) {
        echo 'Avertissements :' . PHP_EOL;

        foreach ($report->get_warnings() as $warning) {
            echo sprintf(
                '[WARNING] %s — %s',
                $warning['code'],
                $warning['message']
            ) . PHP_EOL;

            if ($warning['context'] !== []) {
                echo '       ' .
                    json_encode(
                        $warning['context'],
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    ) .
                    PHP_EOL;
            }
        }

        echo PHP_EOL;
    }

    if ($report->get_errors() !== []) {
        echo 'Erreurs :' . PHP_EOL;

        foreach ($report->get_errors() as $error) {
            echo sprintf(
                '[ERROR] %s — %s',
                $error['code'],
                $error['message']
            ) . PHP_EOL;

            if ($error['context'] !== []) {
                echo '       ' .
                    json_encode(
                        $error['context'],
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    ) .
                    PHP_EOL;
            }
        }

        echo PHP_EOL;
    }

    if (!$report->has_errors()) {
        cli_writeln(
            '[OK] L’architecture Commerce Purchase est opérationnelle.'
        );
    }

    echo PHP_EOL;

    echo sprintf(
        'Résumé : %d avertissement(s), %d erreur(s).',
        count($report->get_warnings()),
        count($report->get_errors())
    ) . PHP_EOL;
}

if (
    !empty($options['strict'])
    && $report->has_errors()
) {
    exit(1);
}

exit(0);