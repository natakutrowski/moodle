<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewService;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Application service for Bundle pricing configuration and quotes. */
final class CommerceBundlePricingService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceBundlePreviewService $preview,
        private readonly CommerceBundlePricingCalculator $calculator
    ) {
    }

    public function get_configuration(string $sku): CommerceBundlePricingConfiguration {
        return CommerceBundlePricingConfiguration::from_product($this->require_bundle($sku));
    }

    /** @param array<string, int|null> $fixedpricesminor */
    public function configure(
        string $sku,
        CommerceBundlePricingConfiguration $configuration,
        array $fixedpricesminor = []
    ): CommerceProduct {
        $bundle = $this->require_bundle($sku);
        $transaction = $this->db->start_delegated_transaction();

        try {
            $saved = $this->products->save(new CommerceProduct(
                $bundle->get_sku(),
                $bundle->get_type(),
                $bundle->get_status(),
                $bundle->get_name(),
                $bundle->get_description(),
                $configuration->apply_to_metadata($bundle->get_metadata()),
                $bundle->get_id(),
                $bundle->get_available_from(),
                $bundle->get_available_until(),
                $bundle->get_time_created(),
                $bundle->get_time_modified()
            ));

            foreach ($fixedpricesminor as $currency => $amountminor) {
                if ($amountminor === null) {
                    continue;
                }
                if ($amountminor < 0) {
                    throw new \coding_exception('A Bundle fixed price cannot be negative.');
                }
                $this->prices->save(new CommerceProductPrice(
                    $saved->get_sku(),
                    CommerceMoney::from_minor($amountminor, strtoupper($currency)),
                    true
                ));
            }

            $transaction->allow_commit();
            return $saved;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    public function quote(string $sku, string $currency): CommerceBundlePriceQuote {
        $bundle = $this->require_bundle($sku);
        $currency = strtoupper(trim($currency));
        $configuration = CommerceBundlePricingConfiguration::from_product($bundle);
        $preview = $this->preview->build($bundle->get_sku());
        $componenttotalminor = 0;
        $componentcomparisoncomplete = true;
        $lines = [];

        foreach ($preview->get_items() as $item) {
            $price = $this->prices->find_active($item->get_product()->get_sku(), $currency);
            if ($price === null) {
                $componentcomparisoncomplete = false;
                if ($configuration->get_strategy() !== CommerceBundlePricingStrategy::FIXED) {
                    throw new \coding_exception(
                        'Missing active ' . $currency . ' price for Bundle component: ' .
                        $item->get_product()->get_sku()
                    );
                }
                continue;
            }
            $linetotal = $price->get_amount_minor() * $item->get_quantity();
            $componenttotalminor += $linetotal;
            $lines[] = [
                'sku' => $item->get_product()->get_sku(),
                'quantity' => $item->get_quantity(),
                'unitamountminor' => $price->get_amount_minor(),
                'totalamountminor' => $linetotal,
            ];
        }

        $fixed = $this->prices->find_active($bundle->get_sku(), $currency);
        return $this->calculator->calculate(
            $bundle->get_sku(),
            $configuration,
            CommerceMoney::from_minor($componenttotalminor, $currency),
            $fixed?->get_money(),
            $lines,
            $componentcomparisoncomplete
        );
    }

    private function require_bundle(string $sku): CommerceProduct {
        $product = $this->products->find_by_sku($sku);
        if ($product === null || !$product->is_bundle()) {
            throw new \coding_exception('Unknown Commerce Bundle product: ' . strtoupper(trim($sku)));
        }
        return $product;
    }
}
