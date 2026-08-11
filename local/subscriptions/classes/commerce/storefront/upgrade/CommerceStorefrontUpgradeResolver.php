<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\upgrade;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontUpgrade;
use local_subscriptions\constants\Operation;
use local_subscriptions\domain\SubscriptionAdvisor;

/** Resolves the canonical Legacy plan upgrade attached to a Native Storefront product. */
final class CommerceStorefrontUpgradeResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function resolve(
        int $userid,
        int $nativeproductid,
        string $currency,
        ?int $legacyplanid = null
    ): ?CommerceStorefrontUpgrade {
        $currency = strtoupper(trim($currency));
        if ($userid <= 0 || $nativeproductid <= 0 || $currency === '') {
            return null;
        }

        if ($legacyplanid === null || $legacyplanid <= 0) {
            $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
                'productid' => $nativeproductid,
                'legacytable' => 'subscription_plan',
            ], 'legacyid', IGNORE_MISSING);
            $legacyplanid = $mapping ? (int)$mapping->legacyid : 0;
        }
        if ($legacyplanid <= 0) {
            return null;
        }

        try {
            $options = SubscriptionAdvisor::advise_options(
                $userid,
                $legacyplanid,
                $currency
            );
        } catch (\Throwable) {
            return null;
        }

        foreach ($options as $option) {
            if ((string)($option['key'] ?? '') !== Operation::UPGRADE_NOW_REPLACE_CHAIN) {
                continue;
            }

            $extra = is_array($option['extra'] ?? null) ? $option['extra'] : [];
            $fromplanid = (int)($extra['from_planid'] ?? 0);
            $toplanid = (int)($extra['to_planid'] ?? $legacyplanid);
            $fromplan = $fromplanid > 0
                ? $this->db->get_record('subscription_plan', ['id' => $fromplanid], 'name', IGNORE_MISSING)
                : null;
            $toplan = $toplanid > 0
                ? $this->db->get_record('subscription_plan', ['id' => $toplanid], 'name', IGNORE_MISSING)
                : null;
            $amountminor = (int)round(((float)($option['amount'] ?? 0.0)) * 100);
            if ($amountminor <= 0) {
                continue;
            }

            return new CommerceStorefrontUpgrade(
                $amountminor,
                strtoupper((string)($option['currency'] ?? $currency)),
                trim((string)($fromplan->name ?? '')),
                trim((string)($toplan->name ?? '')),
                trim((string)($option['summary'] ?? '')),
                $fromplanid,
                $toplanid
            );
        }

        return null;
    }
}
