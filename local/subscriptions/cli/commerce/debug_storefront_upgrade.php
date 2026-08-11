<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontListFilter;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\storefront\upgrade\CommerceStorefrontUpgradeResolver;
use local_subscriptions\domain\SubscriptionAdvisor;

[$options, $unrecognised] = cli_get_params([
    'userid' => 0,
    'target-sku' => '',
    'target-planid' => 0,
    'currency' => 'EUR',
    'lang' => 'fr',
    'json' => false,
    'help' => false,
], [
    'u' => 'userid',
    's' => 'target-sku',
    'p' => 'target-planid',
    'c' => 'currency',
    'l' => 'lang',
    'j' => 'json',
    'h' => 'help',
]);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if (!empty($options['help'])) {
    echo <<<HELP
Read-only diagnostic for Storefront plan upgrades.

Required:
  --userid=ID
And one of:
  --target-sku=SKU
  --target-planid=ID

Optional:
  --currency=EUR
  --lang=fr
  --json

Examples:
  php local/subscriptions/cli/commerce/debug_storefront_upgrade.php \\
    --userid=123 --target-sku=SUB.PLAN.12 --currency=EUR

Note: --target-sku expects the product SKU string, not the numeric product ID.

  php local/subscriptions/cli/commerce/debug_storefront_upgrade.php \\
    --userid=123 --target-planid=12 --currency=EUR

This command never writes to the database.
HELP;
    exit(0);
}

$userid = (int)$options['userid'];
$targetsku = strtoupper(trim((string)$options['target-sku']));
if ($targetsku !== '' && ctype_digit($targetsku)) {
    cli_error('--target-sku expects a SKU string (for example SUB.PLAN.32), not a numeric product ID. Use --target-planid for a plan ID.');
}
$targetplanid = (int)$options['target-planid'];
$currency = strtoupper(trim((string)$options['currency']));
$language = trim((string)$options['lang']) ?: 'fr';

if ($userid <= 0) {
    cli_error('--userid is required.');
}
if ($targetsku === '' && $targetplanid <= 0) {
    cli_error('Provide --target-sku or --target-planid.');
}

$result = [
    'input' => compact('userid', 'targetsku', 'targetplanid', 'currency', 'language'),
    'user' => null,
    'target' => [],
    'upgrade_rules' => [],
    'legacy_subscriptions' => [],
    'native_grants' => [],
    'native_purchases' => [],
    'source_plan_candidates' => [],
    'advisor_options' => [],
    'resolver' => null,
    'storefront_projection' => null,
    'diagnosis' => [],
];

$user = $DB->get_record('user', ['id' => $userid], 'id,username,firstname,lastname,email,deleted,suspended', IGNORE_MISSING);
if (!$user) {
    cli_error('User not found: ' . $userid);
}
$result['user'] = (array)$user;

$targetproduct = null;
if ($targetsku !== '') {
    $targetproduct = $DB->get_record('local_subs_commerce_product', ['sku' => $targetsku], '*', IGNORE_MISSING);
    if ($targetproduct) {
        $mapping = $DB->get_record('local_subs_commerce_prod_map', [
            'productid' => (int)$targetproduct->id,
            'legacytable' => 'subscription_plan',
        ], '*', IGNORE_MISSING);
        if ($mapping && $targetplanid <= 0) {
            $targetplanid = (int)$mapping->legacyid;
        }
    }
}

if ($targetplanid > 0) {
    $targetplan = $DB->get_record('subscription_plan', ['id' => $targetplanid], '*', IGNORE_MISSING);
    if (!$targetproduct) {
        $mapping = $DB->get_record('local_subs_commerce_prod_map', [
            'legacytable' => 'subscription_plan',
            'legacyid' => $targetplanid,
        ], '*', IGNORE_MISSING);
        if ($mapping) {
            $targetproduct = $DB->get_record('local_subs_commerce_product', ['id' => (int)$mapping->productid], '*', IGNORE_MISSING);
            $targetsku = $targetproduct ? strtoupper((string)$targetproduct->sku) : $targetsku;
        }
    }
} else {
    $targetplan = null;
}

$result['target'] = [
    'product' => $targetproduct ? (array)$targetproduct : null,
    'plan' => $targetplan ? (array)$targetplan : null,
    'resolved_sku' => $targetsku,
    'resolved_planid' => $targetplanid,
    'mapping' => $targetproduct ? (array)($DB->get_record('local_subs_commerce_prod_map', [
        'productid' => (int)$targetproduct->id,
        'legacytable' => 'subscription_plan',
    ], '*', IGNORE_MISSING) ?: []) : null,
];

if ($targetplanid > 0) {
    $rules = $DB->get_records('subscription_plan_upgrade', [
        'toplanid' => $targetplanid,
    ], 'id ASC');
    foreach ($rules as $rule) {
        $from = $DB->get_record('subscription_plan', ['id' => (int)$rule->fromplanid], 'id,name,is_active,accessscopeid', IGNORE_MISSING);
        $to = $DB->get_record('subscription_plan', ['id' => (int)$rule->toplanid], 'id,name,is_active,accessscopeid', IGNORE_MISSING);
        $result['upgrade_rules'][] = [
            'rule' => (array)$rule,
            'from_plan' => $from ? (array)$from : null,
            'to_plan' => $to ? (array)$to : null,
        ];
    }
}

$now = time();
$subs = $DB->get_records_sql(
    "SELECT s.*, p.name AS planname, p.is_active AS planactive, p.accessscopeid
       FROM {user_subscription} s
       JOIN {subscription_plan} p ON p.id = s.planid
      WHERE s.userid = :userid
   ORDER BY s.id DESC",
    ['userid' => $userid]
);
$result['legacy_subscriptions'] = array_values(array_map(static fn($r): array => (array)$r, $subs));

$grants = $DB->get_records_sql(
    "SELECT g.*
       FROM {local_subs_commerce_grant} g
      WHERE g.beneficiaryuserid = :userid
   ORDER BY g.id DESC",
    ['userid' => $userid]
);
$result['native_grants'] = array_values(array_map(static fn($r): array => (array)$r, $grants));

$dbman = $DB->get_manager();
$purchasetable = new xmldb_table('local_subscriptions_commerce_purchase');
$itemtable = new xmldb_table('local_subscriptions_commerce_purchase_item');

if (!$dbman->table_exists($purchasetable) || !$dbman->table_exists($itemtable)) {
    $result['native_purchases'] = [];
    $result['diagnosis'][] = 'Native purchase tables are not available in this installation; purchase ownership could not be inspected.';
    $purchases = [];
} else {
    $purchases = $DB->get_records_sql(
    "SELECT p.id AS purchaseid, p.reference, p.status AS purchasestatus,
            p.userid, p.customeremail, i.id AS itemid, i.itemreference, i.itemtype,
            i.quantity, i.unitminor, i.netminor
       FROM {local_subscriptions_commerce_purchase} p
       JOIN {local_subscriptions_commerce_purchase_item} i ON i.purchaseid = p.id
      WHERE p.userid = :userid
   ORDER BY p.id DESC, i.id ASC",
        ['userid' => $userid]
    );
}
$result['native_purchases'] = array_values(array_map(static fn($r): array => (array)$r, $purchases));

// Resolve source plan candidates from every active upgrade rule.
foreach ($result['upgrade_rules'] as $entry) {
    $fromplanid = (int)($entry['rule']['fromplanid'] ?? 0);
    if ($fromplanid <= 0) {
        continue;
    }
    $mapping = $DB->get_record('local_subs_commerce_prod_map', [
        'legacytable' => 'subscription_plan',
        'legacyid' => $fromplanid,
    ], '*', IGNORE_MISSING);
    $sourceproduct = $mapping
        ? $DB->get_record('local_subs_commerce_product', ['id' => (int)$mapping->productid], '*', IGNORE_MISSING)
        : null;
    $sourcesku = $sourceproduct ? strtoupper((string)$sourceproduct->sku) : '';

    $legacymatches = array_values(array_filter($result['legacy_subscriptions'], static function(array $sub) use ($fromplanid, $now): bool {
        return (int)($sub['planid'] ?? 0) === $fromplanid
            && in_array((string)($sub['status'] ?? ''), ['active', 'completed'], true)
            && (int)($sub['start_date'] ?? 0) <= $now
            && ((int)($sub['end_date'] ?? 0) === 0 || (int)$sub['end_date'] >= $now);
    }));
    $grantmatches = $sourcesku === '' ? [] : array_values(array_filter($result['native_grants'], static function(array $grant) use ($sourcesku, $now): bool {
        return strtoupper((string)($grant['productsku'] ?? '')) === $sourcesku
            && in_array((string)($grant['status'] ?? ''), ['active', 'granted', 'completed'], true)
            && (int)($grant['validfrom'] ?? 0) <= $now
            && ((int)($grant['validuntil'] ?? 0) === 0 || (int)$grant['validuntil'] >= $now);
    }));
    $purchasematches = $sourcesku === '' ? [] : array_values(array_filter($result['native_purchases'], static function(array $item) use ($sourcesku): bool {
        return strtoupper((string)($item['itemreference'] ?? '')) === $sourcesku
            && in_array((string)($item['purchasestatus'] ?? ''), ['completed', 'paid', 'succeeded', 'fulfilled'], true);
    }));

    $result['source_plan_candidates'][] = [
        'from_planid' => $fromplanid,
        'mapping' => $mapping ? (array)$mapping : null,
        'source_product' => $sourceproduct ? (array)$sourceproduct : null,
        'source_sku' => $sourcesku,
        'legacy_matches' => $legacymatches,
        'grant_matches' => $grantmatches,
        'purchase_matches' => $purchasematches,
        'owned_by_legacy' => $legacymatches !== [],
        'owned_by_grant' => $grantmatches !== [],
        'owned_by_purchase' => $purchasematches !== [],
    ];
}

if ($targetplanid > 0) {
    try {
        $result['advisor_options'] = SubscriptionAdvisor::advise_options($userid, $targetplanid, $currency);
    } catch (Throwable $e) {
        $result['advisor_options'] = ['exception' => get_class($e) . ': ' . $e->getMessage()];
    }
}

if ($targetproduct && $currency !== '') {
    try {
        $upgrade = (new CommerceStorefrontUpgradeResolver($DB))->resolve(
            $userid,
            (int)$targetproduct->id,
            $currency,
            $targetplanid > 0 ? $targetplanid : null
        );
        $result['resolver'] = $upgrade?->to_array();
    } catch (Throwable $e) {
        $result['resolver'] = ['exception' => get_class($e) . ': ' . $e->getMessage()];
    }
}

// Force the Storefront repository to run with this user as current user.
global $USER;
$previoususer = $USER;
$USER = get_complete_user_data('id', $userid);
try {
    $repository = CommerceStorefrontRepository::create($DB);
    $products = $repository->search(
        new CommerceStorefrontListFilter($language, $currency !== '' ? $currency : null),
        0,
        200
    )->get_products();
    foreach ($products as $product) {
        if (($targetsku !== '' && strtoupper($product->get_sku()) === $targetsku)
            || ($targetproduct && (int)$targetproduct->id > 0 && strtoupper($product->get_sku()) === strtoupper((string)$targetproduct->sku))) {
            $result['storefront_projection'] = $product->to_array();
            break;
        }
    }
} catch (Throwable $e) {
    $result['storefront_projection'] = ['exception' => get_class($e) . ': ' . $e->getMessage()];
} finally {
    $USER = $previoususer;
}

// Human-readable diagnosis.
if (!$targetproduct) {
    $result['diagnosis'][] = 'Target Native product was not found or is not mapped from the target plan.';
}
if ($targetplanid <= 0 || !$targetplan) {
    $result['diagnosis'][] = 'Target Legacy plan was not resolved.';
}
if ($result['upgrade_rules'] === []) {
    $result['diagnosis'][] = 'No upgrade rule points to the target plan.';
}
if ($result['source_plan_candidates'] !== []) {
    foreach ($result['source_plan_candidates'] as $candidate) {
        if (empty($candidate['owned_by_legacy']) && empty($candidate['owned_by_grant']) && empty($candidate['owned_by_purchase'])) {
            $result['diagnosis'][] = 'Source plan ' . $candidate['from_planid'] . ' is not recognised as owned by Legacy subscription, Native grant, or Native purchase.';
        }
        if (empty($candidate['mapping'])) {
            $result['diagnosis'][] = 'Source plan ' . $candidate['from_planid'] . ' has no Native product mapping.';
        }
    }
}
if (isset($result['advisor_options']['exception'])) {
    $result['diagnosis'][] = 'SubscriptionAdvisor failed: ' . $result['advisor_options']['exception'];
} elseif (!array_filter($result['advisor_options'], static fn($option): bool => is_array($option) && (($option['key'] ?? '') === 'upgrade_now_replace_chain'))) {
    $result['diagnosis'][] = 'SubscriptionAdvisor did not return an upgrade option.';
}
if ($result['resolver'] === null) {
    $result['diagnosis'][] = 'CommerceStorefrontUpgradeResolver returned null.';
}
if ($result['storefront_projection'] === null) {
    $result['diagnosis'][] = 'Target product is absent from the Storefront projection for the selected language/currency.';
} elseif (is_array($result['storefront_projection']) && empty($result['storefront_projection']['upgrade'])) {
    $result['diagnosis'][] = 'Target product reached the Storefront, but its upgrade projection is empty.';
}
if ($result['diagnosis'] === []) {
    $result['diagnosis'][] = 'All upgrade layers appear consistent. Inspect the Mustache/presenter rendering next.';
}

if (!empty($options['json'])) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

cli_heading('Commerce Storefront upgrade diagnostic');
cli_writeln('User: ' . fullname($user) . ' (#' . $userid . ', ' . $user->email . ')');
cli_writeln('Target SKU: ' . ($targetsku !== '' ? $targetsku : 'NOT RESOLVED'));
cli_writeln('Target plan: ' . ($targetplan ? $targetplan->name . ' (#' . $targetplanid . ')' : 'NOT RESOLVED'));
cli_writeln('Currency: ' . $currency);
cli_writeln('');

cli_writeln('Upgrade rules: ' . count($result['upgrade_rules']));
foreach ($result['upgrade_rules'] as $entry) {
    cli_writeln(sprintf(
        '  - #%d %s -> %s | active=%s | pricing=%s',
        (int)($entry['rule']['id'] ?? 0),
        (string)($entry['from_plan']['name'] ?? ('plan#' . ($entry['rule']['fromplanid'] ?? 0))),
        (string)($entry['to_plan']['name'] ?? ('plan#' . ($entry['rule']['toplanid'] ?? 0))),
        !empty($entry['rule']['isactive']) ? 'yes' : 'no',
        (string)($entry['rule']['pricingmode'] ?? '')
    ));
}

cli_writeln('');
cli_writeln('Source ownership candidates:');
foreach ($result['source_plan_candidates'] as $candidate) {
    cli_writeln(sprintf(
        '  - plan #%d | sku=%s | mapping=%s | legacy=%s | grant=%s | purchase=%s',
        $candidate['from_planid'],
        $candidate['source_sku'] !== '' ? $candidate['source_sku'] : 'NONE',
        $candidate['mapping'] ? 'yes' : 'no',
        $candidate['owned_by_legacy'] ? 'yes' : 'no',
        $candidate['owned_by_grant'] ? 'yes' : 'no',
        $candidate['owned_by_purchase'] ? 'yes' : 'no'
    ));
}

cli_writeln('');
cli_writeln('SubscriptionAdvisor:');
if (isset($result['advisor_options']['exception'])) {
    cli_writeln('  ERROR: ' . $result['advisor_options']['exception']);
} else {
    foreach ($result['advisor_options'] as $option) {
        cli_writeln(sprintf(
            '  - key=%s amount=%s %s summary=%s',
            (string)($option['key'] ?? ''),
            (string)($option['amount'] ?? ''),
            (string)($option['currency'] ?? ''),
            (string)($option['summary'] ?? '')
        ));
    }
}

cli_writeln('');
cli_writeln('Upgrade resolver: ' . ($result['resolver'] === null ? 'NULL' : json_encode($result['resolver'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
cli_writeln('Storefront product found: ' . ($result['storefront_projection'] === null ? 'no' : 'yes'));
if (is_array($result['storefront_projection']) && $result['storefront_projection'] !== null) {
    cli_writeln('Storefront owned: ' . (!empty($result['storefront_projection']['owned']) ? 'yes' : 'no'));
    cli_writeln('Storefront upgrade: ' . json_encode($result['storefront_projection']['upgrade'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

cli_writeln('');
cli_writeln('Diagnosis:');
foreach ($result['diagnosis'] as $line) {
    cli_writeln('  * ' . $line);
}