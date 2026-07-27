<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewService;
use local_subscriptions\commerce\bundle\service\CommerceBundleReadService;

final class CommerceBundlePreviewAuditor {
    public function __construct(
        private readonly CommerceBundleReadService $bundles,
        private readonly CommerceBundlePreviewService $preview
    ) {
    }

    public function audit(): array {
        $report = [
            'bundles' => 0,
            'previewed' => 0,
            'products' => 0,
            'entitlements' => 0,
            'prices' => 0,
            'errors' => [],
            'details' => [],
        ];

        foreach ($this->bundles->all() as $bundle) {
            $report['bundles']++;

            try {
                $result = $this->preview->build($bundle->get_sku());
                $report['previewed']++;
                $report['products'] += $result->get_product_count();
                $report['entitlements'] += $result->get_entitlement_count();
                $report['prices'] += $result->get_price_count();
                $report['details'][] = $result->to_array();
            } catch (\Throwable $exception) {
                $report['errors'][] = $bundle->get_sku() . ': ' . $exception->getMessage();
            }
        }

        $report['certified'] = $report['errors'] === [];
        return $report;
    }
}
