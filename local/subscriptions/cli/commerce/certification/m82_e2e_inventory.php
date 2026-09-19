<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

global $DB;

$now = time();
$currency = 'EUR';

$fmtdate = static function(?int $timestamp): string {
    if ($timestamp === null || $timestamp <= 0) {
        return '—';
    }
    return userdate($timestamp, '%d/%m/%Y %H:%M');
};

$fmtmoney = static function(int $minor, string $currency): string {
    if ($currency === 'EUR') {
        return number_format($minor / 100, 2, ',', ' ') . ' EUR';
    }
    return $minor . ' ' . $currency . ' (minor)';
};

$iswindowopen = static function(object $product, int $now): bool {
    if ((string)$product->status !== 'active') {
        return false;
    }
    if ($product->availablefrom !== null && (int)$product->availablefrom > $now) {
        return false;
    }
    if ($product->availableuntil !== null && (int)$product->availableuntil < $now) {
        return false;
    }
    return true;
};

$ownership = new CommerceStorefrontOwnershipResolver($DB);
$joineligibility = CommercePedagogicalPromotionJoinEligibilityService::create($DB);
$capacity = CommercePedagogicalCapacityService::create($DB);

echo "=== CAMPUSFR 7.97 M8.2 — REAL E2E INVENTORY ===\n";
echo 'now=' . $now . ' [' . $fmtdate($now) . "]\n";
echo "READ ONLY: this script performs no writes.\n\n";

$products = $DB->get_records_sql(
    "SELECT p.id,
            p.sku,
            p.type,
            p.status,
            p.name,
            p.availablefrom,
            p.availableuntil
       FROM {local_subs_commerce_product} p
   ORDER BY p.type ASC, p.id ASC"
);

$activebytype = [];
foreach ($products as $product) {
    if (!$iswindowopen($product, $now)) {
        continue;
    }

    $rawtype = (string)$product->type;
    $family = match (true) {
        in_array($rawtype, ['course_access', 'subscription', 'course'], true) => 'course_access',
        in_array($rawtype, ['digital', 'digital_download'], true) => 'digital_download',
        $rawtype === 'bundle' => 'bundle',
        default => 'other',
    };

    $activebytype[$family][] = $product;
}

foreach (['course_access', 'digital_download', 'bundle'] as $type) {
    echo "=== BUYABLE PRODUCTS: {$type} ===\n";
    $typed = $activebytype[$type] ?? [];
    if ($typed === []) {
        echo "NONE\n\n";
        continue;
    }

    foreach ($typed as $product) {
        echo "----------------------------------------\n";
        echo "product={$product->id} sku={$product->sku}\n";
        echo "name={$product->name}\n";
        echo "status={$product->status}";
        echo ' available=' . $fmtdate($product->availablefrom !== null ? (int)$product->availablefrom : null);
        echo ' → ' . $fmtdate($product->availableuntil !== null ? (int)$product->availableuntil : null) . "\n";

        $prices = $DB->get_records(
            'local_subs_commerce_prod_price',
            [
                'productid' => (int)$product->id,
                'currency' => $currency,
                'active' => 1,
            ],
            'id ASC'
        );
        if ($prices === []) {
            echo "EUR_PRICE=NONE\n";
        } else {
            foreach ($prices as $price) {
                echo 'EUR_PRICE id=' . $price->id
                    . ' amount=' . $fmtmoney((int)$price->amountminor, $currency)
                    . ' provider=' . ((string)($price->provider ?? '') !== '' ? $price->provider : 'default')
                    . "\n";
            }
        }

        $entitlements = $DB->get_records(
            'local_subs_commerce_prod_ent',
            ['productid' => (int)$product->id],
            'sortorder ASC, id ASC'
        );
        if ($entitlements !== []) {
            foreach ($entitlements as $entitlement) {
                echo 'ENTITLEMENT type=' . $entitlement->type
                    . ' resource=' . $entitlement->resourcekey
                    . "\n";
            }
        }

        if ($type === 'bundle') {
            $components = $DB->get_records_sql(
                "SELECT pc.id,
                        pc.quantity,
                        pc.sortorder,
                        child.id AS childid,
                        child.sku AS childsku,
                        child.type AS childtype,
                        child.status AS childstatus,
                        child.name AS childname
                   FROM {local_subs_commerce_prod_comp} pc
                   JOIN {local_subs_commerce_product} child
                     ON child.id = pc.childproductid
                  WHERE pc.parentproductid = :parentproductid
               ORDER BY pc.sortorder ASC, pc.id ASC",
                ['parentproductid' => (int)$product->id]
            );
            if ($components === []) {
                echo "BUNDLE_COMPONENTS=NONE\n";
            } else {
                foreach ($components as $component) {
                    echo 'COMPONENT sku=' . $component->childsku
                        . ' type=' . $component->childtype
                        . ' status=' . $component->childstatus
                        . ' qty=' . $component->quantity
                        . ' name=' . $component->childname
                        . "\n";
                }
            }
        }

        if ($type === 'course_access') {
            $snapshot = $capacity->for_product((string)$product->sku, $now);
            echo 'PEDAGOGY linked=' . ($snapshot->is_pedagogically_linked() ? 'YES' : 'NO')
                . ' sales_open=' . ($snapshot->are_sales_open() ? 'YES' : 'NO')
                . ' available=' . ($snapshot->is_available() ? 'YES' : 'NO')
                . ' promotion=' . ($snapshot->get_promotion_id() ?? 'NONE')
                . ' remaining=' . ($snapshot->get_remaining() ?? 'UNLIMITED')
                . ' block=' . ($snapshot->get_blocking_reason() ?? 'NONE')
                . "\n";

            if ($snapshot->get_promotion_id() !== null) {
                $promotionid = (int)$snapshot->get_promotion_id();
                $promotion = $DB->get_record(
                    'local_subs_commerce_ped_promo',
                    ['id' => $promotionid],
                    'id,promotionkey,name,status,published,salesopensat,salesclosesat,startsat,endsat,capacitytotal',
                    MUST_EXIST
                );
                echo 'PROMOTION key=' . $promotion->promotionkey
                    . ' name=' . $promotion->name
                    . ' status=' . $promotion->status
                    . ' published=' . $promotion->published
                    . ' sales=' . $fmtdate($promotion->salesopensat !== null ? (int)$promotion->salesopensat : null)
                    . ' → ' . $fmtdate($promotion->salesclosesat !== null ? (int)$promotion->salesclosesat : null)
                    . ' starts=' . $fmtdate($promotion->startsat !== null ? (int)$promotion->startsat : null)
                    . "\n";

                $ownerprices = $DB->get_records(
                    'local_subs_commerce_ped_jprice',
                    [
                        'promotionid' => $promotionid,
                        'productid' => (int)$product->id,
                        'currency' => $currency,
                    ],
                    'id ASC'
                );
                foreach ($ownerprices as $ownerprice) {
                    echo 'OWNER_PRICE=' . $fmtmoney((int)$ownerprice->amountminor, $currency) . "\n";
                }
            }
        }
    }
    echo "\n";
}

// Real DEV users: prefer explicit DEV accounts, then higher IDs to avoid using personal historical accounts by accident.
$users = $DB->get_records_select(
    'user',
    'deleted = 0 AND suspended = 0 AND id > 1',
    [],
    'id DESC',
    'id,email,firstname,lastname'
);
$users = array_values($users);
usort($users, static function(object $a, object $b): int {
    $adev = str_starts_with(strtolower((string)$a->email), 'dev');
    $bdev = str_starts_with(strtolower((string)$b->email), 'dev');
    if ($adev !== $bdev) {
        return $adev ? -1 : 1;
    }
    return ((int)$b->id) <=> ((int)$a->id);
});

echo "=== E2E USER CANDIDATES BY COURSE PRODUCT ===\n";
foreach ($activebytype['course_access'] ?? [] as $product) {
    $sku = (string)$product->sku;
    echo "----------------------------------------\n";
    echo "sku={$sku} name={$product->name}\n";

    $fresh = [];
    $owners = [];
    foreach ($users as $user) {
        $userid = (int)$user->id;
        $source = $ownership->resolve_source($userid, $sku);
        if ($source === 'none') {
            if (count($fresh) < 5) {
                $fresh[] = $user;
            }
            continue;
        }

        if (count($owners) < 5) {
            $decision = $joineligibility->resolve($userid, $sku, $now);
            if ($decision->is_eligible()) {
                $owners[] = [$user, $source, $decision];
            }
        }

        if (count($fresh) >= 5 && count($owners) >= 5) {
            break;
        }
    }

    echo "FRESH_CANDIDATES\n";
    if ($fresh === []) {
        echo "  NONE\n";
    }
    foreach ($fresh as $user) {
        echo '  user=' . $user->id . ' email=' . $user->email . "\n";
    }

    echo "OWNER_PROMOTION_JOIN_CANDIDATES\n";
    if ($owners === []) {
        echo "  NONE\n";
    }
    foreach ($owners as [$user, $source, $decision]) {
        $context = $decision->get_context();
        echo '  user=' . $user->id
            . ' email=' . $user->email
            . ' ownership=' . $source
            . ' promotion=' . $context->get_promotion_id()
            . ' remaining=' . ($context->get_remaining() ?? 'UNLIMITED')
            . "\n";
    }
}

echo "\n=== RECOMMENDED M8.2 REAL MATRIX ===\n";
echo "A fresh course purchase (prefer a course linked to a progressive promotion)\n";
echo "B owner promotion_join on the same course SKU\n";
echo "C standalone course with no current pedagogical promotion, if available\n";
echo "D standalone digital purchase\n";
echo "E standalone bundle purchase\n";
echo "F mixed cart: course + digital\n";
echo "G mixed cart: course + digital + bundle\n";
echo "H mixed cart: promotion_join + digital\n";
echo "For each paid scenario verify purchase/items/payments/grants plus concrete course/digital/promotion rights.\n";
