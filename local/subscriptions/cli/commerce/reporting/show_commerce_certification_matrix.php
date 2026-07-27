<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceCertificationMatrix;

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'json' => false, 'enabled-only' => false],
    ['h' => 'help', 'j' => 'json', 'e' => 'enabled-only']
);

if ($unrecognized) {
    cli_error('Options inconnues : ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo <<<HELP
Matrice de certification Commerce CampusFR.

Ce script ne modifie aucune donnée.

--json, -j          Sortie JSON.
--enabled-only, -e  Affiche uniquement les scénarios activés.
--help, -h          Affiche cette aide.

HELP;
    exit(0);
}

$rows = (new CommerceCertificationMatrix())->to_array();

if (!empty($options['enabled-only'])) {
    $rows = array_values(array_filter($rows, static fn(array $row): bool => !empty($row['enabled'])));
}

if (!empty($options['json'])) {
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

cli_heading('CampusFR Commerce certification matrix');

foreach ($rows as $row) {
    echo sprintf(
        '[%s] %s — %s/%s — %s — mode=%s',
        !empty($row['enabled']) ? 'ON' : 'OFF',
        $row['key'],
        $row['provider'],
        $row['currency'],
        $row['purchase_kind'],
        $row['payment_mode']
    ) . PHP_EOL;
    echo '     toggle: ' . $row['toggle'] . PHP_EOL;
    echo '     checks: ' . implode(', ', $row['checks']) . PHP_EOL;
}

exit(0);
