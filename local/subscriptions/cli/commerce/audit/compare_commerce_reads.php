<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\read\CommerceReadCoordinatorFactory;
use local_subscriptions\commerce\read\policy\CommerceReadConsumer;

[$options] = cli_get_params(
    ['family' => null, 'id' => null, 'consumer' => CommerceReadConsumer::TASK],
    ['f' => 'family', 'i' => 'id', 'c' => 'consumer']
);

if (empty($options['family']) || empty($options['id'])) {
    cli_error('Usage: --family=subscription|digital --id=LEGACY_ID [--consumer=task]');
}

$result = CommerceReadCoordinatorFactory::create()->read_purchase(
    (string)$options['consumer'],
    (string)$options['family'],
    (int)$options['id']
);

echo json_encode([
    'source' => $result->source,
    'success' => $result->is_success(),
    'shadow_compared' => $result->shadowcompared,
    'shadow_severity' => $result->shadowseverity,
    'differences' => $result->differences,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
