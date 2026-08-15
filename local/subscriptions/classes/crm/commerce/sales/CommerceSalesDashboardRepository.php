<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\sales;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutCrmService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;

/** Operational KPI read model for the CRM sales workspace. */
final class CommerceSalesDashboardRepository {
    /** @var string[] */
    private const SUCCESS_PAYMENT_STATUSES = ['paid', 'succeeded', 'completed', 'captured'];

    public function __construct(
        private readonly CommercePurchaseReadRepository $purchases,
        private readonly ?CommerceUnfinishedGuestCheckoutCrmService $unfinished = null
    ) {}

    /**
     * @return array{
     *   total:int,
     *   revenue:array<string,int>,
     *   personaloffers:int,
     *   pending:int,
     *   failed:int,
     *   invalidcheckouts:int
     * }
     */
    public function snapshot(CommercePurchaseListFilter $filter): array {
        $summaries = $this->purchases->summaries_for_metrics($filter);
        $revenue = [];
        $personaloffers = 0;
        $pending = 0;
        $failed = 0;

        foreach ($summaries as $summary) {
            if (!$summary instanceof CommercePurchaseSummary) {
                continue;
            }

            if (in_array(strtolower($summary->paymentstatus), self::SUCCESS_PAYMENT_STATUSES, true)) {
                $currency = strtoupper($summary->currency);
                $revenue[$currency] = ($revenue[$currency] ?? 0) + $summary->totalminor;
            }

            if ($summary->haspersonaloffer) {
                $personaloffers++;
            }

            if (in_array($summary->commercialstatus, [
                CommerceCommercialStatus::PENDING,
                CommerceCommercialStatus::PAID,
                CommerceCommercialStatus::TO_FULFILL,
                CommerceCommercialStatus::PARTIALLY_FULFILLED,
            ], true)) {
                $pending++;
            }

            if (in_array($summary->commercialstatus, [
                CommerceCommercialStatus::PAYMENT_FAILED,
                CommerceCommercialStatus::CANCELLED,
            ], true)) {
                $failed++;
            }
        }

        ksort($revenue);
        $unfinished = $this->unfinished ?? CommerceUnfinishedGuestCheckoutCrmService::create();

        return [
            'total' => count($summaries),
            'revenue' => $revenue,
            'personaloffers' => $personaloffers,
            'pending' => $pending,
            'failed' => $failed,
            'invalidcheckouts' => count($unfinished->queue()),
        ];
    }
}
