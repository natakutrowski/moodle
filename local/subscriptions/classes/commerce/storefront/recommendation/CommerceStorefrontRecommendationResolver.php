<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\storefront\recommendation;
defined('MOODLE_INTERNAL') || die();

/** Resolves explicit, deterministic Storefront recommendations from Native metadata. */
final class CommerceStorefrontRecommendationResolver {
    /** @return string[] */
    public function resolve(array $metadata, string $currentsku = ''): array {
        $storefront = is_array($metadata['storefront'] ?? null) ? $metadata['storefront'] : [];
        $raw = is_array($storefront['recommendations'] ?? null) ? $storefront['recommendations'] : [];
        $currentsku = strtoupper(trim($currentsku));
        $result = [];

        foreach ($raw as $sku) {
            $sku = strtoupper(trim((string)$sku));
            if (
                $sku !== ''
                && $sku !== $currentsku
                && !in_array($sku, $result, true)
            ) {
                $result[] = $sku;
            }

            if (count($result) >= 4) {
                break;
            }
        }

        return $result;
    }
}
