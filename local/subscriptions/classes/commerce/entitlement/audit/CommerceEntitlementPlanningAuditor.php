<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchasePreparationService;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * Read-only shadow audit for Native catalogue entitlement planning.
 */
final class CommerceEntitlementPlanningAuditor {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogPurchasePreparationService $preparationservice,
        private readonly CommerceEntitlementGrantPlanner $planner
    ) {
    }

    public function audit(string $language = 'fr'): array {
        $report = [
            'checked' => 0,
            'equal' => 0,
            'different' => 0,
            'definitions' => 0,
            'planned' => 0,
            'errors' => [],
            'details' => [],
        ];

        foreach ($this->db->get_records('subscription_plan', ['is_active' => 1], 'id ASC') as $plan) {
            $prices = $this->db->get_records(
                'subscription_plan_price',
                ['planid' => $plan->id],
                'currency ASC'
            );

            foreach ($prices as $price) {
                $this->compare(
                    $report,
                    'subscription_plan#' . $plan->id . ':' . strtoupper((string)$price->currency),
                    'SUB.PLAN.' . $plan->id,
                    strtoupper((string)$price->currency),
                    $language
                );
            }
        }

        foreach ($this->db->get_records('subscription_digital_product', ['enabled' => 1], 'id ASC') as $product) {
            $sku = 'DIGITAL.' . strtoupper((string)$product->slug);

            foreach (['EUR', 'RUB'] as $currency) {
                $amountfield = $currency === 'EUR' ? 'price_eur' : 'price_rub';

                if ((float)$product->{$amountfield} < 0) {
                    continue;
                }

                $this->compare(
                    $report,
                    'subscription_digital_product#' . $product->id . ':' . $currency,
                    $sku,
                    $currency,
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
        string $language
    ): void {
        $report['checked']++;

        try {
            $preparation = $this->preparationservice->prepare(
                'entitlement-shadow-' . substr(hash('sha256', $source), 0, 24),
                new CommerceCustomer(null, 'entitlement-audit@example.invalid'),
                [['sku' => $sku]],
                $currency,
                $language
            );

            $requestitem = $preparation->get_request()->get_items()[0];
            $definitions = $requestitem->get_metadata_value('entitlements', []);
            $definitioncount = is_array($definitions) ? count($definitions) : 0;
            $plan = $this->planner->plan($preparation, time());
            $plannedcount = $plan->count();
            $status = $definitioncount === $plannedcount ? 'equal' : 'different';

            $report[$status]++;
            $report['definitions'] += $definitioncount;
            $report['planned'] += $plannedcount;
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'currency' => $currency,
                'status' => $status,
                'definitions' => $definitioncount,
                'planned' => $plannedcount,
            ];
        } catch (\Throwable $exception) {
            $report['different']++;
            $report['errors'][] = $source . ': ' . $exception->getMessage();
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'currency' => $currency,
                'status' => 'error',
                'definitions' => null,
                'planned' => null,
            ];
        }
    }
}
