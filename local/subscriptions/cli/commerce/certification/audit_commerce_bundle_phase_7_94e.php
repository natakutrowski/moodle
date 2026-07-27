<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\bundle\audit\CommerceBundlePhaseCertificationAuditor;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options] = cli_get_params([
    'json' => false,
    'strict' => false,
    'help' => false,
], [
    'h' => 'help',
]);

if ($options['help']) {
    echo "Certifies phase 7.94E Commerce Products and Bundles.\n\n";
    echo "--json    Output JSON\n--strict  Return a non-zero exit code when certification fails\n";
    exit(0);
}

$result = (new CommerceBundlePhaseCertificationAuditor(new CommerceCatalogFactory($DB)))->audit();

if ($options['json']) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "== Phase 7.94E - Commerce Products and Bundles certification ==\n";
    printf("products:       %d\n", $result['products']);
    printf("bundles:        %d\n", $result['bundles']);
    printf("previewed:      %d\n", $result['previewed']);
    printf("pricing quotes: %d\n", $result['pricingquotes']);
    printf("translations:   %d\n", $result['translations']);
    printf("components:     %d\n", $result['components']);
    printf("entitlements:   %d\n", $result['entitlements']);
    printf("CRM pages:      %d\n", $result['requiredpages']);
    printf("errors:         %d\n", count($result['errors']));
    printf("certified:      %s\n", $result['certified'] ? 'yes' : 'no');

    foreach ($result['errors'] as $error) {
        echo "- {$error}\n";
    }
}

if ($options['strict'] && !$result['certified']) {
    exit(1);
}
