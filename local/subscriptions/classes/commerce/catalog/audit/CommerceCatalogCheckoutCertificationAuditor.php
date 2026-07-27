<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\checkout\CommerceCatalogCheckoutService;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/** Certifies all active Legacy-mapped products through the guarded Native checkout simulation. */
final class CommerceCatalogCheckoutCertificationAuditor {
    public function __construct(private readonly \moodle_database $db, private readonly CommerceCatalogCheckoutService $checkout) {}

    public function audit(string $language = 'fr'): array {
        $report = ['checked' => 0, 'passed' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => [], 'details' => []];
        foreach ($this->db->get_records('subscription_plan', ['is_active' => 1], 'id ASC') as $plan) {
            foreach ($this->db->get_records('subscription_plan_price', ['planid' => $plan->id], 'currency ASC') as $price) {
                if ($this->major_to_minor($price->price) <= 0) {
                    $this->skip($report, 'SUB.PLAN.' . $plan->id, strtoupper((string)$price->currency), 'non_payable_price');
                    continue;
                }
                $this->check($report, 'SUB.PLAN.' . $plan->id, strtoupper((string)$price->currency), $language);
            }
        }
        foreach ($this->db->get_records('subscription_digital_product', ['enabled' => 1], 'id ASC') as $product) {
            $sku = 'DIGITAL.' . strtoupper(preg_replace('/[^A-Z0-9._:-]+/i', '.', (string)$product->slug));
            foreach (['EUR' => $product->price_eur, 'RUB' => $product->price_rub] as $currency => $amount) {
                if ($this->major_to_minor($amount) <= 0) {
                    $this->skip($report, $sku, $currency, 'non_payable_price');
                    continue;
                }
                $this->check($report, $sku, $currency, $language);
            }
        }
        return $report;
    }

    private function skip(array &$report, string $sku, string $currency, string $reason): void {
        $report['skipped']++;
        $report['details'][] = [
            'sku' => $sku,
            'currency' => $currency,
            'status' => 'skipped',
            'reason' => $reason,
        ];
    }

    private function major_to_minor(mixed $amount): int {
        $normalised = str_replace(',', '.', trim((string)$amount));
        return (int)round(((float)$normalised) * 100);
    }

    private function check(array &$report, string $sku, string $currency, string $language): void {
        $report['checked']++;
        try {
            $result = $this->checkout->initialize(
                'c9-' . strtolower(str_replace(['.', ':'], '-', $sku)) . '-' . strtolower($currency),
                new CommerceCustomer(null, 'catalogue-certification@example.invalid'),
                [['sku' => $sku]], $currency, $language, null,
                'https://example.invalid/success', 'https://example.invalid/cancel', false, false,
                ['certification' => '7.94C9']
            );
            $initialization = $result->get_initialization();
            $valid = $initialization->is_simulated() && $initialization->get_validation()->is_valid();
            $report[$valid ? 'passed' : 'failed']++;
            $report['details'][] = ['sku' => $sku, 'currency' => $currency, 'status' => $valid ? 'passed' : 'failed', 'provider' => $initialization->get_provider_key()];
        } catch (\Throwable $e) {
            $report['failed']++;
            $report['errors'][] = $sku . ':' . $currency . ': ' . $e->getMessage();
            $report['details'][] = ['sku' => $sku, 'currency' => $currency, 'status' => 'error'];
        }
    }
}
