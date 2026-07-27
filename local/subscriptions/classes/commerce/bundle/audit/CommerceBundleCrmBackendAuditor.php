<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductManager;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

/**
 * Read-only certification of the unified CRM Commerce product backend.
 */
final class CommerceBundleCrmBackendAuditor {
    public function __construct(
        private readonly CommerceCatalogProductManager $manager,
        private readonly CommerceProductTypeRegistry $types
    ) {
    }

    public function audit(): array {
        $report = [
            'products' => 0,
            'bundles' => 0,
            'prices' => 0,
            'translations' => 0,
            'components' => 0,
            'entitlements' => 0,
            'previewed' => 0,
            'errors' => [],
            'details' => [],
            'bundletyperegistered' => $this->types->has(CommerceProductType::BUNDLE),
            'certified' => false,
        ];

        foreach ($this->manager->list_products() as $summary) {
            $report['products']++;
            $report['prices'] += $summary->get_price_count();
            $report['translations'] += $summary->get_translation_count();
            $report['components'] += $summary->get_component_count();
            $report['entitlements'] += $summary->get_entitlement_count();
            $detail = $summary->to_array();

            if ($summary->get_product()->is_bundle()) {
                $report['bundles']++;

                try {
                    $preview = $this->manager->preview_bundle($summary->get_sku());
                    $report['previewed']++;
                    $detail['preview'] = [
                        'items' => $preview->get_item_count(),
                        'quantity' => $preview->get_total_quantity(),
                        'maximumdepth' => $preview->get_maximum_depth(),
                    ];
                } catch (\Throwable $exception) {
                    $report['errors'][] = $summary->get_sku() . ': ' . $exception->getMessage();
                    $detail['previewerror'] = $exception->getMessage();
                }
            }

            $report['details'][] = $detail;
        }

        $report['certified'] = $report['bundletyperegistered']
            && $report['errors'] === []
            && $report['previewed'] === $report['bundles'];

        return $report;
    }
}
