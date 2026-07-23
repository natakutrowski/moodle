<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\CommerceLegacyComparator;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
        'json' => false,
        'batch-size' => 200,
        'max-differences' => 100,
    ],
    [
        'h' => 'help',
        's' => 'strict',
        'j' => 'json',
        'b' => 'batch-size',
        'm' => 'max-differences',
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
Comparaison détaillée Legacy ↔ Commerce.

Cette commande vérifie que les valeurs historiques importantes sont
conservées lors de leur transformation en objets Commerce.

La commande est strictement en lecture seule.

Options :

--strict, -s
    Retourne un code de sortie non nul si une différence est détectée.

--json, -j
    Retourne le rapport complet au format JSON.

--batch-size=200, -b=200
    Nombre d'enregistrements traités par lot.

--max-differences=100, -m=100
    Nombre maximal de différences affichées en mode texte.

--help, -h
    Affiche cette aide.

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

$maxdifferences = max(
    1,
    (int)$options['max-differences']
);

$comparator = new CommerceLegacyComparator();
$result = $comparator->compare($batchsize);

if (!empty($options['json'])) {
    echo json_encode(
        $result->to_array(),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    echo PHP_EOL;
} else {
    cli_heading(
        'CampusFR Legacy ↔ Commerce comparison'
    );

    echo PHP_EOL;
    echo 'Compteurs :' . PHP_EOL;

    foreach (
        $result->get_counters()
        as $key => $value
    ) {
        echo sprintf(
            '  %-45s %d',
            $key,
            $value
        ) . PHP_EOL;
    }

    echo PHP_EOL;

    if (!$result->has_differences()) {
        cli_writeln(
            '[OK] Aucune différence détectée.'
        );
    } else {
        cli_writeln(
            '[WARNING] Différences détectées :'
        );

        echo PHP_EOL;

        $differences = array_slice(
            $result->get_differences(),
            0,
            $maxdifferences
        );

        foreach ($differences as $difference) {
            echo sprintf(
                '[%s #%d] %s',
                strtoupper(
                    (string)$difference['type']
                ),
                (int)$difference['legacyid'],
                (string)$difference['field']
            ) . PHP_EOL;

            echo '    Legacy   : ' .
                json_encode(
                    $difference['legacy'],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ) .
                PHP_EOL;

            echo '    Commerce : ' .
                json_encode(
                    $difference['commerce'],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ) .
                PHP_EOL;
        }

        $hidden = count(
            $result->get_differences()
        ) - count($differences);

        if ($hidden > 0) {
            echo PHP_EOL;

            cli_writeln(
                sprintf(
                    '%d différence(s) supplémentaire(s) non affichée(s).',
                    $hidden
                )
            );
        }
    }
}

if (
    !empty($options['strict'])
    && $result->has_differences()
) {
    exit(1);
}

exit(0);