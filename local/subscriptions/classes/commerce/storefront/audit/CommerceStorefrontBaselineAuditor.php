<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\audit;

defined('MOODLE_INTERNAL') || die();

/** Static baseline inventory for the pre-refactor public Commerce surface. */
final class CommerceStorefrontBaselineAuditor {
    /** @return array<string, mixed> */
    public function audit(string $pluginroot): array {
        $publicfiles = [
            'subscribe.php',
            'checkout.php',
            'digital_catalog.php',
            'digital_product.php',
            'my_purchases.php',
            'payment_success.php',
            'payment_error.php',
            'payment_cancel.php',
            'digital_success.php',
        ];
        $legacytables = [
            'subscription_plan',
            'subscription_plan_price',
            'subscription_digital_product',
            'subscription_digital_product_lang',
            'subscription_payment_request',
        ];

        $existing = [];
        $legacyreferences = [];
        $inlinecssfiles = [];
        $totallines = 0;

        foreach ($publicfiles as $relativepath) {
            $path = $pluginroot . '/' . $relativepath;
            if (!is_file($path)) {
                continue;
            }
            $source = (string)file_get_contents($path);
            $existing[$relativepath] = substr_count($source, "\n") + 1;
            $totallines += $existing[$relativepath];

            foreach ($legacytables as $table) {
                if (strpos($source, "'{$table}'") !== false || strpos($source, '"' . $table . '"') !== false) {
                    $legacyreferences[$relativepath][] = $table;
                }
            }
            if (stripos($source, '<style') !== false || strpos($source, "'style' =>") !== false) {
                $inlinecssfiles[] = $relativepath;
            }
        }

        $templates = glob($pluginroot . '/templates/*.mustache') ?: [];
        $storefronttemplates = glob($pluginroot . '/templates/storefront/*.mustache') ?: [];

        return [
            'publicfiles' => $existing,
            'publicfilecount' => count($existing),
            'totallines' => $totallines,
            'legacyreferences' => $legacyreferences,
            'inlinecssfiles' => array_values(array_unique($inlinecssfiles)),
            'templatecount' => count($templates),
            'storefronttemplatecount' => count($storefronttemplates),
            'hasunifiedcatalogue' => is_file(
                $pluginroot . '/classes/commerce/catalog/readmodel/CommerceCatalogReadRepository.php'
            ),
            'hasstorefrontreadmodel' => is_file(
                $pluginroot . '/classes/commerce/storefront/repository/CommerceStorefrontRepository.php'
            ),
        ];
    }
}
