<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\url\UrlFactory;

/** Centralises public Storefront links without exposing Legacy identities in templates. */
final class CommerceStorefrontUrlResolver {
    public static function details(
        CommerceStorefrontProduct $product,
        ?string $currency = null
    ): \moodle_url {
        $params = [];
        if ($currency !== null && $currency !== '') {
            $params['currency'] = strtoupper($currency);
        }

        return CommerceProductDiscoveryUrlResolver::resolve(
            $product->get_sku(),
            $product->get_type(),
            $product->get_metadata(),
            $params
        );
    }

    public static function direct_storefront(
        CommerceStorefrontProduct $product,
        ?string $currency = null,
        array $params = []
    ): \moodle_url {
        if ($currency !== null && $currency !== '') {
            $params['currency'] = strtoupper($currency);
        }

        return CommerceProductDiscoveryUrlResolver::storefront(
            $product->get_sku(),
            $product->get_type(),
            $product->get_metadata(),
            $params
        );
    }

    public static function owned_access(
        CommerceStorefrontProduct $product
    ): \moodle_url {
        return match ($product->get_type()) {
            'subscription', 'course_access' =>
                self::owned_course_access($product),
            'digital', 'digital_download' =>
                UrlFactory::my_digital_products(),
            default =>
                UrlFactory::my_purchases(),
        };
    }

    private static function owned_course_access(
        CommerceStorefrontProduct $product
    ): \moodle_url {
        global $DB;

        $nativeproduct = $DB->get_record(
            'local_subs_commerce_product',
            ['sku' => strtoupper(trim($product->get_sku()))],
            'id',
            IGNORE_MISSING
        );

        if ($nativeproduct) {
            $entitlements = $DB->get_records(
                'local_subs_commerce_prod_ent',
                [
                    'productid' => (int)$nativeproduct->id,
                    'type' => 'course_access',
                ],
                'sortorder ASC, id ASC',
                'resourcekey,configurationjson'
            );

            foreach ($entitlements as $entitlement) {
                $courseid = self::course_id_from_entitlement(
                    (string)$entitlement->resourcekey,
                    (string)($entitlement->configurationjson ?? '')
                );
                if ($courseid > 0) {
                    return UrlFactory::course($courseid);
                }
            }
        }

        foreach ($product->get_legacy_references() as $reference) {
            if (
                ($reference['table'] ?? '') !== 'subscription_plan'
                || (int)($reference['id'] ?? 0) <= 0
            ) {
                continue;
            }

            $legacy = $DB->get_record_sql(
                'SELECT courseid
                   FROM {subscription_plan_entitlement}
                  WHERE planid = :planid
                    AND courseid > 0
               ORDER BY priority DESC, id ASC',
                ['planid' => (int)$reference['id']],
                IGNORE_MULTIPLE
            );

            if ($legacy && (int)$legacy->courseid > 0) {
                return UrlFactory::course((int)$legacy->courseid);
            }
        }

        return UrlFactory::my_courses();
    }

    private static function course_id_from_entitlement(
        string $resourcekey,
        string $configurationjson
    ): int {
        if (
            preg_match(
                '/^course:(\\d+)(?::[a-z0-9_-]+)?$/i',
                trim($resourcekey),
                $matches
            ) === 1
        ) {
            return (int)$matches[1];
        }

        $configuration = json_decode($configurationjson, true);
        return is_array($configuration)
            ? max(0, (int)($configuration['courseid'] ?? 0))
            : 0;
    }

    public static function quick_purchase(
        CommerceStorefrontProduct $product,
        ?string $currency = null
    ): \moodle_url {
        foreach ($product->get_legacy_references() as $reference) {
            $table = (string)($reference['table'] ?? '');
            $id = (int)($reference['id'] ?? 0);

            if ($table === 'subscription_plan' && $id > 0) {
                return UrlFactory::checkout($id, $currency);
            }
            if ($table === 'subscription_digital_product' && $id > 0) {
                global $DB;
                $legacy = $DB->get_record('subscription_digital_product', ['id' => $id], 'slug', IGNORE_MISSING);
                if ($legacy && trim((string)$legacy->slug) !== '') {
                    return UrlFactory::digital_product((string)$legacy->slug);
                }
            }
        }

        return self::direct_storefront($product, $currency);
    }
}
