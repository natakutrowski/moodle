<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'family' => 'all',
    'execute' => false,
    'strict' => false,
], [
    'h' => 'help',
]);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if (!empty($options['help'])) {
    echo "Audit and repair Legacy catalogue mappings to Native storefront products.\n\n";
    echo "php local/subscriptions/cli/commerce/migration/repair_legacy_storefront_mappings.php\n";
    echo "php local/subscriptions/cli/commerce/migration/repair_legacy_storefront_mappings.php --execute\n";
    echo "php local/subscriptions/cli/commerce/migration/repair_legacy_storefront_mappings.php --family=subscription|digital --strict\n";
    exit(0);
}

$family = strtolower(trim((string)$options['family']));
if (!in_array($family, ['all', 'subscription', 'digital'], true)) {
    cli_error('Unsupported family. Use all, subscription or digital.');
}

$execute = !empty($options['execute']);
$strict = !empty($options['strict']);
$resolver = new CommerceLegacyStorefrontProductResolver($DB);
$definitions = [
    'subscription' => ['table' => 'subscription_plan', 'label' => 'subscription plans'],
    'digital' => ['table' => 'subscription_digital_product', 'label' => 'digital products'],
];
$families = $family === 'all' ? array_keys($definitions) : [$family];
$unresolved = 0;
$recoverable = 0;
$alreadylinked = 0;

cli_heading('Legacy to Native storefront mappings');
cli_writeln($execute ? 'MODE: EXECUTE' : 'MODE: DRY-RUN');

foreach ($families as $currentfamily) {
    $table = $definitions[$currentfamily]['table'];
    cli_writeln('');
    cli_writeln(ucfirst($definitions[$currentfamily]['label']) . ':');

    foreach ($DB->get_records($table, null, 'id ASC', 'id') as $legacy) {
        $mappingexists = $DB->record_exists('local_subs_commerce_prod_map', [
            'legacytable' => $table,
            'legacyid' => (int)$legacy->id,
        ]);
        $product = $resolver->resolve($table, (int)$legacy->id, $execute);
        if (!$product) {
            $unresolved++;
            cli_writeln('  [MISSING] ' . $table . '#' . $legacy->id);
            continue;
        }

        if ($mappingexists) {
            $alreadylinked++;
            continue;
        }

        $recoverable++;
        cli_writeln('  [' . ($execute ? 'REPAIRED' : 'RECOVERABLE') . '] '
            . $table . '#' . $legacy->id . ' -> ' . $product->sku);
    }
}

cli_writeln('');
cli_writeln('Already mapped: ' . $alreadylinked);
cli_writeln(($execute ? 'Repaired' : 'Recoverable') . ': ' . $recoverable);
cli_writeln('Unresolved: ' . $unresolved);

if ($unresolved > 0 && $strict) {
    exit(1);
}
exit(0);
