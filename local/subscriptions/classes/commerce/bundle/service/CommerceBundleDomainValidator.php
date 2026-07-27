<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\domain\CommerceBundle;
use local_subscriptions\commerce\bundle\domain\CommerceBundleCollection;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Validates the composition graph of Native Commerce bundles.
 */
final class CommerceBundleDomainValidator {

    public function __construct(
        private readonly CommerceProductRepository $products
    ) {
    }

    public function validate(CommerceBundleCollection $bundles): array {
        $report = [
            'bundles' => count($bundles),
            'components' => 0,
            'empty' => 0,
            'unknown' => 0,
            'disabled' => 0,
            'duplicates' => 0,
            'selfreferences' => 0,
            'cycles' => 0,
            'errors' => [],
            'details' => [],
        ];

        foreach ($bundles as $bundle) {
            $detail = $this->validate_bundle($bundle, $bundles);
            $report['components'] += $detail['components'];

            foreach (['empty', 'unknown', 'disabled', 'duplicates', 'selfreferences', 'cycles'] as $key) {
                $report[$key] += $detail[$key];
            }

            $report['errors'] = array_merge($report['errors'], $detail['errors']);
            $report['details'][] = $detail;
        }

        return $report;
    }

    private function validate_bundle(
        CommerceBundle $bundle,
        CommerceBundleCollection $bundles
    ): array {
        $detail = [
            'sku' => $bundle->get_sku(),
            'status' => 'valid',
            'components' => $bundle->get_component_count(),
            'empty' => 0,
            'unknown' => 0,
            'disabled' => 0,
            'duplicates' => 0,
            'selfreferences' => 0,
            'cycles' => 0,
            'errors' => [],
        ];

        if ($bundle->is_empty()) {
            $detail['empty'] = 1;
            $detail['errors'][] = $bundle->get_sku() . ': bundle has no component.';
        }

        $seen = [];

        foreach ($bundle->get_components() as $component) {
            $childsku = $component->get_child_product_sku();

            if ($childsku === $bundle->get_sku()) {
                $detail['selfreferences']++;
                $detail['errors'][] = $bundle->get_sku() . ': bundle contains itself.';
            }

            if (isset($seen[$childsku])) {
                $detail['duplicates']++;
                $detail['errors'][] = $bundle->get_sku() . ': duplicate component ' . $childsku . '.';
            }

            $seen[$childsku] = true;
            $child = $this->products->find_by_sku($childsku);

            if ($child === null) {
                $detail['unknown']++;
                $detail['errors'][] = $bundle->get_sku() . ': unknown component ' . $childsku . '.';
                continue;
            }

            if (!$child->is_active()) {
                $detail['disabled']++;
                $detail['errors'][] = $bundle->get_sku() . ': inactive component ' . $childsku . '.';
            }
        }

        if ($this->contains_cycle($bundle->get_sku(), $bundle->get_sku(), $bundles, [])) {
            $detail['cycles'] = 1;
            $detail['errors'][] = $bundle->get_sku() . ': recursive composition cycle detected.';
        }

        if ($detail['errors'] !== []) {
            $detail['status'] = 'invalid';
        }

        return $detail;
    }

    private function contains_cycle(
        string $rootsku,
        string $currentsku,
        CommerceBundleCollection $bundles,
        array $path
    ): bool {
        if (isset($path[$currentsku])) {
            return $currentsku === $rootsku;
        }

        $bundle = $bundles->get($currentsku);

        if ($bundle === null) {
            return false;
        }

        $path[$currentsku] = true;

        foreach ($bundle->get_components() as $component) {
            $childsku = $component->get_child_product_sku();

            if ($childsku === $rootsku) {
                return true;
            }

            if ($this->contains_cycle($rootsku, $childsku, $bundles, $path)) {
                return true;
            }
        }

        return false;
    }
}
