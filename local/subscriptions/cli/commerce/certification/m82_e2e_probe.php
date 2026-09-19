<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

global $DB;

$options = getopt('', ['email:', 'since::', 'sku::']);
$email = strtolower(trim((string)($options['email'] ?? '')));
if ($email === '') {
    fwrite(STDERR, "Usage: php m82_e2e_probe.php --email=user@example.test [--since=UNIX] [--sku=SKU1,SKU2]\n");
    exit(2);
}

$user = $DB->get_record('user', ['email' => $email, 'deleted' => 0], 'id,email,firstname,lastname,suspended', IGNORE_MISSING);
if (!$user) {
    fwrite(STDERR, "No active Moodle user found for {$email}.\n");
    exit(3);
}

$since = isset($options['since']) && trim((string)$options['since']) !== ''
    ? max(0, (int)$options['since'])
    : time() - 7200;
$skus = [];
if (isset($options['sku']) && trim((string)$options['sku']) !== '') {
    foreach (explode(',', (string)$options['sku']) as $sku) {
        $sku = strtoupper(trim($sku));
        if ($sku !== '') {
            $skus[$sku] = true;
        }
    }
}
$skus = array_keys($skus);

$dt = static fn(int $ts): string => $ts > 0 ? userdate($ts, '%d/%m/%Y %H:%M:%S') : '—';
$money = static fn(int $minor, string $currency): string => number_format($minor / 100, 2, ',', ' ') . ' ' . strtoupper($currency);
$decode = static function(?string $json): array {
    $value = json_decode((string)$json, true);
    return is_array($value) ? $value : [];
};

$ownership = new CommerceStorefrontOwnershipResolver($DB);

echo "=== CAMPUSFR 7.97 M8.2 — E2E PROBE ===\n";
echo "READ ONLY: this script performs no writes.\n";
echo 'user=' . $user->id . ' email=' . $user->email
    . ' name=' . trim((string)$user->firstname . ' ' . (string)$user->lastname)
    . ' suspended=' . (int)$user->suspended . "\n";
echo 'since=' . $since . ' [' . $dt($since) . "]\n\n";

if ($skus !== []) {
    echo "=== OWNERSHIP SNAPSHOT ===\n";
    foreach ($skus as $sku) {
        $product = $DB->get_record('local_subs_commerce_product', ['sku' => $sku], 'id,sku,type,status,name', IGNORE_MISSING);
        if (!$product) {
            echo "sku={$sku} PRODUCT_NOT_FOUND\n";
            continue;
        }
        echo 'sku=' . $sku
            . ' product=' . $product->id
            . ' type=' . $product->type
            . ' status=' . $product->status
            . ' ownership=' . $ownership->resolve_source((int)$user->id, $sku)
            . ' name=' . $product->name . "\n";
    }
    echo "\n";
}

echo "=== RECENT PURCHASES ===\n";
$purchases = $DB->get_records_select(
    'local_subscriptions_commerce_purchase',
    'userid = :userid AND timecreated >= :since',
    ['userid' => (int)$user->id, 'since' => $since],
    'timecreated ASC, id ASC'
);
if ($purchases === []) {
    echo "NONE\n";
}
foreach ($purchases as $purchase) {
    echo "----------------------------------------\n";
    echo 'PURCHASE id=' . $purchase->id
        . ' ref=' . $purchase->reference
        . ' type=' . $purchase->type
        . ' status=' . $purchase->status
        . ' total=' . $money((int)$purchase->totalminor, (string)$purchase->currency)
        . ' created=' . $dt((int)$purchase->timecreated) . "\n";

    $items = $DB->get_records('local_subscriptions_commerce_purchase_item', ['purchaseid' => (int)$purchase->id], 'position ASC, id ASC');
    foreach ($items as $item) {
        $metadata = array_replace($decode($item->fulfillmentjson ?? ''), $decode($item->metadatajson ?? ''));
        $operation = trim((string)($metadata['operation'] ?? ''));
        echo '  ITEM pos=' . $item->position
            . ' type=' . $item->itemtype
            . ' ref=' . $item->itemreference
            . ' qty=' . $item->quantity
            . ' net=' . $money((int)$item->netminor, (string)$item->currency)
            . ($operation !== '' ? ' operation=' . $operation : '')
            . "\n";
    }

    $payments = $DB->get_records('local_subscriptions_commerce_payment', ['purchaseid' => (int)$purchase->id], 'sequence ASC, id ASC');
    if ($payments === []) {
        echo "  PAYMENT NONE\n";
    }
    foreach ($payments as $payment) {
        echo '  PAYMENT seq=' . $payment->sequence
            . ' provider=' . ($payment->provider ?? '—')
            . ' status=' . $payment->status
            . ' amount=' . $money((int)$payment->amountminor, (string)$payment->currency)
            . ' paid=' . $dt((int)($payment->paidat ?? 0))
            . "\n";
    }

    $grants = $DB->get_records('local_subs_commerce_grant', ['purchasereference' => (string)$purchase->reference], 'id ASC');
    if ($grants === []) {
        echo "  GRANT NONE\n";
    }
    foreach ($grants as $grant) {
        $gmeta = $decode($grant->metadatajson ?? '');
        echo '  GRANT ref=' . $grant->grantreference
            . ' sku=' . $grant->productsku
            . ' type=' . $grant->type
            . ' resource=' . $grant->resourcekey
            . ' status=' . $grant->status
            . (!empty($gmeta['purchasedsku']) ? ' purchasedsku=' . $gmeta['purchasedsku'] : '')
            . (!empty($gmeta['expandedsku']) ? ' expandedsku=' . $gmeta['expandedsku'] : '')
            . "\n";
    }

    $digital = $DB->get_records('local_subs_commerce_dig_access', ['purchasereference' => (string)$purchase->reference], 'id ASC');
    foreach ($digital as $access) {
        echo '  DIGITAL sku=' . $access->productsku
            . ' resource=' . $access->resourcekey
            . ' status=' . $access->status
            . ' downloads=' . $access->downloadcount
            . "\n";
    }

    $joins = $DB->get_records('local_subs_commerce_ped_join', ['purchasereference' => (string)$purchase->reference], 'id ASC');
    foreach ($joins as $join) {
        echo '  PED_JOIN promotion=' . $join->promotionid
            . ' course=' . $join->courseid
            . ' product=' . $join->productid
            . ' state=' . $join->state . "\n";
    }
}

echo "\n=== CURRENT COURSE / PEDAGOGY RIGHTS ===\n";
$accesses = $DB->get_records('local_subs_commerce_ped_access', ['userid' => (int)$user->id], 'courseid ASC, id ASC');
if ($accesses === []) {
    echo "PED_ACCESS NONE\n";
}
foreach ($accesses as $access) {
    echo 'PED_ACCESS course=' . $access->courseid
        . ' promotion=' . ($access->promotionid ?? 'NONE')
        . ' profile=' . $access->profile . "\n";
}

$enrolments = $DB->get_records_sql(
    "SELECT ue.id, e.courseid, e.enrol, ue.status
       FROM {user_enrolments} ue
       JOIN {enrol} e ON e.id = ue.enrolid
      WHERE ue.userid = :userid
   ORDER BY e.courseid ASC, ue.id ASC",
    ['userid' => (int)$user->id]
);
if ($enrolments === []) {
    echo "MOODLE_ENROLMENT NONE\n";
}
foreach ($enrolments as $enrolment) {
    echo 'MOODLE_ENROLMENT course=' . $enrolment->courseid
        . ' enrol=' . $enrolment->enrol
        . ' status=' . $enrolment->status . "\n";
}

$memberships = $DB->get_records_sql(
    "SELECT gm.id,
            gm.active,
            g.id AS pedagogicalgroupid,
            g.promotionid,
            g.displayname,
            g.productid,
            g.moodlegroupid
       FROM {local_subs_commerce_ped_gmem} gm
       JOIN {local_subs_commerce_ped_group} g ON g.id = gm.groupid
      WHERE gm.userid = :userid
   ORDER BY g.promotionid ASC, g.position ASC, gm.id ASC",
    ['userid' => (int)$user->id]
);
if ($memberships === []) {
    echo "PED_GROUP NONE\n";
}
foreach ($memberships as $membership) {
    echo 'PED_GROUP promotion=' . $membership->promotionid
        . ' group=' . $membership->pedagogicalgroupid
        . ' moodlegroup=' . $membership->moodlegroupid
        . ' product=' . ($membership->productid ?? 'NONE')
        . ' active=' . $membership->active
        . ' name=' . $membership->displayname . "\n";
}

if ($skus !== []) {
    echo "\n=== CURRENT NATIVE GRANTS FOR REQUESTED SKUS ===\n";
    [$insql, $inparams] = $DB->get_in_or_equal($skus, SQL_PARAMS_NAMED, 'sku');
    $params = ['userid' => (int)$user->id] + $inparams;
    $grants = $DB->get_records_select(
        'local_subs_commerce_grant',
        "beneficiaryuserid = :userid AND productsku {$insql}",
        $params,
        'timecreated ASC, id ASC'
    );
    if ($grants === []) {
        echo "NONE\n";
    }
    foreach ($grants as $grant) {
        echo 'GRANT purchase=' . $grant->purchasereference
            . ' sku=' . $grant->productsku
            . ' type=' . $grant->type
            . ' resource=' . $grant->resourcekey
            . ' status=' . $grant->status
            . ' created=' . $dt((int)$grant->timecreated) . "\n";
    }
}
