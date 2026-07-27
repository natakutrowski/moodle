<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

/** Final certification of the complete 7.94E Commerce Products and Bundles phase. */
final class CommerceBundlePhaseCertificationAuditor {
    public function __construct(
        private readonly CommerceCatalogFactory $factory
    ) {
    }

    public function audit(): array {
        $manager = $this->factory->product_manager();
        $products = $manager->list_products();
        $bundles = 0;
        $previewed = 0;
        $pricedquotes = 0;
        $translations = 0;
        $components = 0;
        $entitlements = 0;
        $errors = [];

        foreach ($products as $summary) {
            $product = $summary->get_product();
            $translations += $summary->get_translation_count();
            $components += $summary->get_component_count();
            $entitlements += $summary->get_entitlement_count();

            if (!$product->is_bundle()) {
                continue;
            }

            $bundles++;

            try {
                $manager->preview_bundle($product->get_sku());
                $previewed++;
            } catch (\Throwable $exception) {
                if ($product->get_status() !== 'draft') {
                    $errors[] = $product->get_sku() . ': preview: ' . $exception->getMessage();
                }
            }

            foreach (['EUR', 'RUB'] as $currency) {
                try {
                    $this->factory->bundle_pricing_service()->quote($product->get_sku(), $currency);
                    $pricedquotes++;
                } catch (\Throwable $exception) {
                    if ($product->get_status() === 'active') {
                        $errors[] = $product->get_sku() . ': ' . $currency . ': ' . $exception->getMessage();
                    }
                }
            }
        }

        $requiredpages = [
            'admin/commerce/products/index.php',
            'admin/commerce/products/view.php',
            'admin/commerce/products/edit.php',
            'admin/commerce/products/components.php',
            'admin/commerce/products/preview.php',
            'admin/commerce/products/pricing.php',
        ];
        $missingpages = [];
        foreach ($requiredpages as $relativepath) {
            if (!is_readable(__DIR__ . '/../../../../' . $relativepath)) {
                $missingpages[] = $relativepath;
            }
        }
        foreach ($missingpages as $missingpage) {
            $errors[] = 'Missing CRM page: ' . $missingpage;
        }

        return [
            'products' => count($products),
            'bundles' => $bundles,
            'previewed' => $previewed,
            'pricingquotes' => $pricedquotes,
            'translations' => $translations,
            'components' => $components,
            'entitlements' => $entitlements,
            'requiredpages' => count($requiredpages),
            'missingpages' => $missingpages,
            'errors' => $errors,
            'certified' => $errors === [],
        ];
    }
}
