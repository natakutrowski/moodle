<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockStructureMigrator;

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'showroom' => 'third-group-verbs',
    'dry-run' => false,
], [
    'h' => 'help',
]);

if ($unrecognized !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo "Normalize the historical Showroom block structure.\n\n";
    echo "Options:\n";
    echo "  --showroom=KEY   Showroom key (default: third-group-verbs)\n";
    echo "  --dry-run        Report changes without writing\n";
    echo "  -h, --help       Show this help\n";
    exit(0);
}

global $DB;

$migrator = new CommerceShowroomBlockStructureMigrator($DB);
$result = $migrator->migrate(
    (string)$options['showroom'],
    0,
    !empty($options['dry-run'])
);

cli_writeln('Changed: ' . $result['changed']);
cli_writeln('Created: ' . $result['created']);
cli_writeln('Converted legacy blocks: ' . $result['converted']);
cli_writeln('Reordered: ' . ($result['reordered'] ? 'yes' : 'no'));
