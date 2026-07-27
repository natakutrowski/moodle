<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(
    ['help' => false, 'json' => false],
    ['h' => 'help', 'j' => 'json']
);

if ($options['help']) {
    echo "Audit direct Commerce reads in local/subscriptions.\n\n";
    echo "Options:\n  --json  Emit JSON\n";
    exit(0);
}

$root = realpath(__DIR__ . '/..');
$patterns = [
    'DIRECT_LEGACY_READ' => '/\{local_subscriptions(?:_digital_purchase|_payment_request)?\}/',
    'DIRECT_NATIVE_READ' => '/\{local_subscriptions_commerce_(?:purchase|purchase_item|payment|fulfillment)\}/',
    'READ_SERVICE' => '/Commerce(?:Purchase|Payment|Fulfillment|Entitlement|CustomerHistory)ReadService/',
    'LEGACY_FALLBACK' => '/legacy[_ ]fallback|CommerceLegacy/i',
    'SHADOW_COMPARE' => '/shadow[_ ]compare|ShadowComparator/i',
];

$results = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $content = file_get_contents($path);
    foreach ($patterns as $classification => $pattern) {
        if (preg_match($pattern, $content)) {
            $results[$classification][] = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        }
    }
}

ksort($results);
foreach ($results as &$files) {
    $files = array_values(array_unique($files));
    sort($files);
}
unset($files);

if ($options['json']) {
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

foreach ($results as $classification => $files) {
    echo $classification . ': ' . count($files) . PHP_EOL;
    foreach ($files as $file) {
        echo '  - ' . $file . PHP_EOL;
    }
}
