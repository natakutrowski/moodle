<?php

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipeline;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * Read-only end-to-end audit of Native catalogue selection through payment lines.
 *
 * It never persists a purchase and never calls a payment provider.
 */
final class CommerceCatalogPaymentPipelineAuditor {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogPaymentPipeline $pipeline
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
            $result = $this->pipeline->build(
                'pipeline-shadow-' . strtolower(str_replace(['#', ':', '.'], '-', $source)),
                new CommerceCustomer(null, 'pipeline-shadow@example.invalid'),
                [['sku' => $sku]],
                $currency,
                $language
            );

            $preparation = $result->get_preparation();
            $paymentrequest = $result->get_payment_request();
            $requestitem = $preparation->get_request()->get_items()[0];
            $differences = [];

            if ($preparation->get_total_amount_minor() !== $legacyamountminor) {
                $differences[] = 'prepared_amount_minor';
            }
            if ($paymentrequest->get_amount_minor() !== $legacyamountminor) {
                $differences[] = 'payment_amount_minor';
            }
            if (array_sum(array_map(static fn($line): int => $line->get_total_amount_minor(), $paymentrequest->get_lines())) !== $legacyamountminor) {
                $differences[] = 'payment_lines_total';
            }
            if ($requestitem->get_item()->get_legacy_id() !== $legacyid) {
                $differences[] = 'legacy_id';
            }
            if ($requestitem->get_metadata_value('legacyfamily') !== $legacyfamily) {
                $differences[] = 'legacy_family';
            }
            if ($paymentrequest->get_currency() !== $currency) {
                $differences[] = 'currency';
            }

            $status = $differences === [] ? 'equal' : 'different';
            $report[$status]++;
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'status' => $status,
                'differences' => $differences,
                'paymentlines' => count($paymentrequest->get_lines()),
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
        return (int)round(((float)str_replace(',', '.', trim((string)$amount))) * 100);
    }
}
