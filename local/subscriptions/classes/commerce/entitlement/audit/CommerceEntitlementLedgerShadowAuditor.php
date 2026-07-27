<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchasePreparationService;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * Read-only audit of planned grants against the Native entitlement ledger.
 */
final class CommerceEntitlementLedgerShadowAuditor {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceCatalogPurchasePreparationService $preparationservice,
        private readonly CommerceEntitlementGrantPlanner $planner,
        private readonly CommerceEntitlementGrantRepository $repository
    ) {
    }

    public function audit(string $language = 'fr'): array {
        $report = [
            'checked' => 0,
            'grants' => 0,
            'create' => 0,
            'identical' => 0,
            'conflict' => 0,
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
                $currency = strtoupper((string)$price->currency);

                $this->inspect(
                    $report,
                    'subscription_plan#' . $plan->id . ':' . $currency,
                    'SUB.PLAN.' . $plan->id,
                    $currency,
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

                $this->inspect(
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

    private function inspect(
        array &$report,
        string $source,
        string $sku,
        string $currency,
        string $language
    ): void {
        $report['checked']++;

        try {
            $reference = 'entitlement-ledger-shadow-' . substr(hash('sha256', $source), 0, 20);
            $preparation = $this->preparationservice->prepare(
                $reference,
                new CommerceCustomer(null, 'entitlement-ledger-audit@example.invalid'),
                [['sku' => $sku]],
                $currency,
                $language
            );
            $plan = $this->planner->plan($preparation, 1_700_000_000);
            $classifications = [
                'create' => 0,
                'identical' => 0,
                'conflict' => 0,
            ];

            foreach ($plan->get_grants() as $grant) {
                $classification = $this->repository->classify($grant);
                $classifications[$classification]++;
                $report[$classification]++;
                $report['grants']++;
            }

            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'currency' => $currency,
                'status' => $classifications['conflict'] > 0 ? 'conflict' : 'ready',
                'grants' => $plan->count(),
                'create' => $classifications['create'],
                'identical' => $classifications['identical'],
                'conflict' => $classifications['conflict'],
            ];
        } catch (\Throwable $exception) {
            $report['errors'][] = $source . ': ' . $exception->getMessage();
            $report['details'][] = [
                'source' => $source,
                'sku' => $sku,
                'currency' => $currency,
                'status' => 'error',
                'grants' => null,
                'create' => null,
                'identical' => null,
                'conflict' => null,
            ];
        }
    }
}
