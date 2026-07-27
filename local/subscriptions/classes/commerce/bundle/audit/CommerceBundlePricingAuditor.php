<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingService;
use local_subscriptions\commerce\bundle\service\CommerceBundleReadService;

final class CommerceBundlePricingAuditor {
    public function __construct(
        private readonly CommerceBundleReadService $bundles,
        private readonly CommerceBundlePricingService $pricing
    ) {
    }

    public function audit(array $currencies = ['EUR', 'RUB']): array {
        $report = ['bundles' => 0, 'configured' => 0, 'quotes' => 0, 'errors' => [], 'details' => []];
        foreach ($this->bundles->all() as $bundle) {
            $report['bundles']++;
            try {
                $configuration = $this->pricing->get_configuration($bundle->get_sku());
                $report['configured']++;
                $detail = ['sku' => $bundle->get_sku(), 'configuration' => $configuration->to_array(), 'quotes' => []];
                foreach ($currencies as $currency) {
                    try {
                        $quote = $this->pricing->quote($bundle->get_sku(), $currency);
                        $detail['quotes'][$currency] = $quote->to_array();
                        $report['quotes']++;
                    } catch (\Throwable $exception) {
                        $detail['quotes'][$currency] = ['error' => $exception->getMessage()];
                    }
                }
                $report['details'][] = $detail;
            } catch (\Throwable $exception) {
                $report['errors'][] = $bundle->get_sku() . ': ' . $exception->getMessage();
            }
        }
        $report['certified'] = $report['errors'] === [];
        return $report;
    }
}
