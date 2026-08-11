<?php

declare(strict_types=1);

namespace local_subscriptions\url;

use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;

defined('MOODLE_INTERNAL') || die();

/** Resolves customer-facing URLs without exposing implementation paths. */
final class CommerceCustomerPublicUrlResolver {
    public static function product(string $sku, array $params = []): \moodle_url {
        global $DB;

        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return UrlFactory::storefront($params);
        }

        $record = $DB->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            'type,metadatajson',
            IGNORE_MISSING
        );
        if (!$record) {
            return new \moodle_url(
                '/local/subscriptions/storefront_product.php',
                ['sku' => $sku] + $params
            );
        }

        $metadata = json_decode((string)$record->metadatajson, true);
        return CommerceProductDiscoveryUrlResolver::resolve(
            $sku,
            (string)$record->type,
            is_array($metadata) ? $metadata : [],
            $params
        );
    }


    /**
     * Resolves the direct product Storefront, bypassing discovery routing.
     *
     * Use this for explicit "product page" links on owned/customer surfaces.
     * Discovery surfaces should continue to use product().
     */
    public static function storefront(string $sku, array $params = []): \moodle_url {
        global $DB;

        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return UrlFactory::storefront($params);
        }

        $record = $DB->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            'type,metadatajson',
            IGNORE_MISSING
        );
        if (!$record) {
            return new \moodle_url(
                '/local/subscriptions/storefront_product.php',
                ['sku' => $sku] + $params
            );
        }

        $metadata = json_decode((string)$record->metadatajson, true);
        return CommerceProductDiscoveryUrlResolver::storefront(
            $sku,
            (string)$record->type,
            is_array($metadata) ? $metadata : [],
            $params
        );
    }

    public static function course(int $courseid): \moodle_url {
        return UrlFactory::course($courseid);
    }

    public static function order(string $reference): \moodle_url {
        return UrlFactory::order_details(['reference' => $reference]);
    }
}
