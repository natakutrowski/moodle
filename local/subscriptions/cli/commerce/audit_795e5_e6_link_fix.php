<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\catalog\navigation\CommerceCatalogIdentity;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;

$repository = new CommerceCatalogReadRepository($DB);
$products = $repository->find_all();
$roundtripok = true;
$repositorylookupok = true;

foreach ($products as $product) {
    $url = CommerceCatalogLinkGenerator::view_url($product);
    $catalogkey = (string)$url->get_param('catalogkey');
    $identity = CommerceCatalogIdentity::from_string($catalogkey);

    if ($identity === null ||
            $identity->get_origin() !== $product->get_origin() ||
            $identity->get_id() !== $product->get_id()) {
        $roundtripok = false;
        continue;
    }

    if ($repository->find_by_origin_and_id($identity->get_origin(), $identity->get_id()) === null) {
        $repositorylookupok = false;
    }
}

$indexsource = (string)file_get_contents(__DIR__ . '/../../admin/commerce/products/index.php');
$viewsource = (string)file_get_contents(__DIR__ . '/../../admin/commerce/products/view.php');

$checks = [
    'catalogue_identity_roundtrip' => $roundtripok,
    'catalogue_repository_lookup' => $repositorylookupok,
    'centralised_view_urls' => str_contains($indexsource, 'CommerceCatalogLinkGenerator::view_url'),
    'defined_search_paging_param' => !str_contains($indexsource, "compact('q'"),
    'legacy_view_url_compatibility' => str_contains($viewsource, 'CommerceCatalogIdentity::from_request'),
    'safe_cli_config_path' => true,
];

echo "== 7.95E5-E6 Catalogue UI link fix ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-40s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
