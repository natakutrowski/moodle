<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\service\CommerceBundleReadService;
use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;

/**
 * Read-only certification of recursive bundle expansion.
 */
final class CommerceBundleExpansionAuditor {
    public function __construct(
        private readonly CommerceBundleReadService $bundles,
        private readonly CommerceBundleExpansionService $expander
    ) {
    }

    public function audit(): array {
        $report = [
            'checked' => 0,
            'expanded' => 0,
            'leafitems' => 0,
            'totalquantity' => 0,
            'maximumdepth' => 0,
            'errors' => [],
            'details' => [],
            'certified' => true,
        ];

        foreach ($this->bundles->all() as $bundle) {
            $report['checked']++;

            try {
                $result = $this->expander->expand($bundle->get_sku());
                $report['expanded']++;
                $report['leafitems'] += $result->get_item_count();
                $report['totalquantity'] += $result->get_total_quantity();
                $report['maximumdepth'] = max(
                    $report['maximumdepth'],
                    $result->get_maximum_depth()
                );
                $report['details'][] = [
                    'sku' => $bundle->get_sku(),
                    'status' => 'expanded',
                    'items' => $result->get_item_count(),
                    'quantity' => $result->get_total_quantity(),
                    'bundlesvisited' => $result->get_bundles_visited(),
                    'maximumdepth' => $result->get_maximum_depth(),
                    'expansion' => $result->to_array()['items'],
                ];
            } catch (\Throwable $exception) {
                $report['errors'][] = $bundle->get_sku() . ': ' . $exception->getMessage();
                $report['details'][] = [
                    'sku' => $bundle->get_sku(),
                    'status' => 'error',
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $report['certified'] = $report['errors'] === [];

        return $report;
    }
}
