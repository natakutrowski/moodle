<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignRequest;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;

[$options, $unrecognized] = cli_get_params([
    'help' => false, 'execute' => false, 'campaign' => '', 'source-sku' => '', 'target-sku' => '',
    'price' => '', 'discount' => '', 'percent' => 0, 'valid-from' => '', 'expires' => '',
    'include-owned-target' => false, 'limit' => 0, 'csv' => '',
], ['h' => 'help']);
if ($unrecognized || $options['help']) {
    echo "Personal Offer CRM campaign (dry-run by default)\n\n"
        . "--campaign=KEY --source-sku=SKU --target-sku=SKU\n"
        . "Pricing: --price=EUR:3000,RUB:299000 OR --discount=EUR:900 OR --percent=20\n"
        . "Optional: --expires=YYYY-MM-DD --valid-from=YYYY-MM-DD --limit=N --csv=/path/file.csv\n"
        . "Write only with: --execute\n";
    exit($unrecognized ? 1 : 0);
}
foreach (['campaign', 'source-sku', 'target-sku'] as $required) {
    if (trim((string)$options[$required]) === '') { cli_error('--' . $required . ' is required.'); }
}
$target = $DB->get_record('local_subs_commerce_product', ['sku' => strtoupper(trim((string)$options['target-sku']))], 'id,sku', MUST_EXIST);
$parseamounts = static function(string $raw): array {
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', $raw))) as $part) {
        [$currency, $minor] = array_pad(explode(':', $part, 2), 2, null);
        if (!$currency || $minor === null || !ctype_digit($minor)) { cli_error('Invalid currency amount: ' . $part); }
        $out[strtoupper($currency)] = (int)$minor;
    }
    return $out;
};
$pricingcount = ((string)$options['price'] !== '' ? 1 : 0) + ((string)$options['discount'] !== '' ? 1 : 0) + ((int)$options['percent'] > 0 ? 1 : 0);
if ($pricingcount !== 1) { cli_error('Specify exactly one pricing option: --price, --discount or --percent.'); }
if ((string)$options['price'] !== '') { $terms = CommercePersonalOfferTerms::fixed_price($parseamounts((string)$options['price'])); }
elseif ((string)$options['discount'] !== '') { $terms = CommercePersonalOfferTerms::fixed_discount($parseamounts((string)$options['discount'])); }
else { $terms = CommercePersonalOfferTerms::percentage_discount((int)round(((float)$options['percent']) * 100)); }
$parsedate = static function(string $raw): ?int {
    if ($raw === '') { return null; }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $raw, new DateTimeZone('UTC'));
    if (!$dt || $dt->format('Y-m-d') !== $raw) { cli_error('Invalid date: ' . $raw); }
    return $dt->getTimestamp();
};
$request = new CommercePersonalOfferCampaignRequest(
    (string)$options['campaign'], (string)$options['source-sku'], (int)$target->id, $terms,
    $parsedate((string)$options['valid-from']), $parsedate((string)$options['expires']),
    !$options['include-owned-target'], null
);
$result = CommercePersonalOfferCampaignService::create($DB)->run($request, (bool)$options['execute'], max(0, (int)$options['limit']));
echo ($options['execute'] ? "EXECUTE" : "DRY-RUN") . "\n";
foreach ($result['summary'] as $key => $value) { echo str_pad($key, 24) . ': ' . $value . "\n"; }
foreach ($result['rows'] as $row) { echo sprintf("%-22s %-28s %s\n", $row['status'], $row['reference'], $row['email']); }
if ((string)$options['csv'] !== '') {
    $fp = fopen((string)$options['csv'], 'wb'); if (!$fp) { cli_error('Unable to open CSV path.'); }
    fputcsv($fp, ['purchase_id','purchase_reference','email','userid','status','offer_uuid','personal_offer_url','message']);
    foreach ($result['rows'] as $row) { fputcsv($fp, [$row['purchaseid'],$row['reference'],$row['email'],$row['userid'],$row['status'],$row['offeruuid'],$row['url'],$row['message']]); }
    fclose($fp); echo 'CSV: ' . $options['csv'] . "\n";
}
exit($result['summary']['errors'] > 0 ? 1 : 0);
