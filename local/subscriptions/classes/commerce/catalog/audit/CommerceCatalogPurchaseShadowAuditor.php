<?php

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchaseRequestFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * Compares Native catalogue purchase requests with Legacy catalogue values.
 *
 * The audit is read-only and does not initialize a payment provider.
 */
final class CommerceCatalogPurchaseShadowAuditor {

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogPurchaseRequestFactory $requestfactory
    ) {
    }

    public function audit(string $language = 'fr'): array {
        $report = [
            'checked' => 0,
            'equal' => 0,
            'different' => 0,
            'errors' => [],
            'details' => [],
        ];

        foreach ($this->db->get_records('subscription_plan', ['is_active' => 1], 'id ASC') as $plan) {
            foreach ($this->db->get_records('subscription_plan_price', ['planid' => $plan->id], 'currency ASC') as $price) {
                $this->compare(
                    $report,
                    'subscription_plan#' . $plan->id . ':' . strtoupper($price->currency),
                    'SUB.PLAN.' . $plan->id,
                    strtoupper((string)$price->currency),
                    $this->major_to_minor($price->price),
                    'subscription',
                    (int)$plan->id,
                    $language
                );
            }
        }

        foreach ($this->db->get_records('subscription_digital_product', ['enabled' => 1], 'id ASC') as $product) {
            $sku = 'DIGITAL.' . strtoupper(preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$product->slug));
            foreach (['EUR' => $product->price_eur, 'RUB' => $product->price_rub] as $currency => $amount) {
                $this->compare(
                    $report,
                    'subscription_digital_product#' . $product->id . ':' . $currency,
                    $sku,
                    $currency,
                    $this->major_to_minor($amount),
                    'digital',
                    (int)$product->id,
                    $language
                );
            }
        }

        return $report;
    }

    private function compare(
        array &$report,
        string $source,
        string $sku,
        string $currency,
        int $legacyamountminor,
        string $legacyfamily,
        int $legacyid,
        string $language
    ): void {
        $report['checked']++;
        try {
            $request = $this->requestfactory->create(
                'shadow-' . strtolower(str_replace(['#', ':', '.'], '-', $source)),
                new CommerceCustomer(null, 'shadow-audit@example.invalid'),
                [['sku' => $sku]],
                $currency,
                $language
            );
            $item = $request->get_items()[0];
            $differences = [];
            if ($request->get_total_amount_minor() !== $legacyamountminor) {
                $differences[] = 'amount_minor';
            }
            if ($item->get_item()->get_legacy_id() !== $legacyid) {
                $differences[] = 'legacy_id';
            }
            if ($item->get_metadata_value('legacyfamily') !== $legacyfamily) {
                $differences[] = 'legacy_family';
            }

            $status = $differences === [] ? 'equal' : 'different';
            $report[$status]++;
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'status' => $status,
                'differences' => $differences,
            ];
        } catch (\Throwable $exception) {
            $report['different']++;
            $report['errors'][] = $source . ': ' . $exception->getMessage();
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'status' => 'error',
                'differences' => ['exception'],
            ];
        }
    }

    private function major_to_minor(mixed $amount): int {
        $normalised = str_replace(',', '.', trim((string)$amount));
        return (int)round(((float)$normalised) * 100);
    }
}
