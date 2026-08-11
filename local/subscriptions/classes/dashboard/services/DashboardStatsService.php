<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\currency\CurrencyFormatter;
use local_subscriptions\currency\CurrencyPreferenceService;
use local_subscriptions\dashboard\repositories\DashboardStatsRepository;
use local_subscriptions\dashboard\value\DashboardRevenue;
use local_subscriptions\commerce\customer\analytics\CommerceCustomerAnalyticsRepository;

/**
 * Builds Dashboard statistics for one period.
 */
final class DashboardStatsService {

    public function __construct(
        private readonly DashboardStatsRepository $repository =
            new DashboardStatsRepository(),
        private readonly CurrencyPreferenceService $currencypreferences =
            new CurrencyPreferenceService(),
        private readonly ?CommerceCustomerAnalyticsRepository $nativeanalytics = null
    ) {
    }

    public function load(
        string $period = DashboardPeriod::TODAY,
        ?int $userid = null
    ): \stdClass {
        global $USER;

        $period = DashboardPeriod::normalize($period);
        $range = DashboardPeriod::range($period);
        $userid = $userid ?? (int)$USER->id;

        $stats = new \stdClass();

        $stats->period = $period;
        $stats->periodlabel = DashboardPeriod::label($period);

        $stats->newusers = $this->repository->count_new_users(
            $range['start'],
            $range['end']
        );

        $stats->newtrials =
            $this->repository->count_new_trials(
                $range['start'],
                $range['end']
            );

        $stats->newcustomers =
            $this->repository->count_new_customers(
                $range['start'],
                $range['end']
            );

        $stats->trialcustomerratio =
            $this->calculate_trial_customer_ratio(
                $stats->newtrials,
                $stats->newcustomers
            );

        $stats->digitalpurchases =
            $this->repository->count_digital_purchases(
                $range['start'],
                $range['end']
            );

        $revenues = $this->repository->get_revenue_by_currency(
            $range['start'],
            $range['end']
        );

        $stats->revenues = $revenues;
        $stats->availablecurrencies = array_keys($revenues);

        $stats->selectedcurrency =
            $this->currencypreferences->resolve(
                $userid,
                $stats->availablecurrencies
            );

        $selectedrevenue = $revenues[$stats->selectedcurrency]
            ?? new DashboardRevenue(
                $stats->selectedcurrency,
                0.0,
                0.0
            );

        $stats->revenue = $selectedrevenue->total();

        $stats->formattedrevenue = CurrencyFormatter::format(
            $stats->revenue,
            $stats->selectedcurrency
        );

        $stats->formattedrevenues =
            $this->format_revenues($revenues);

        global $DB;
        $native = ($this->nativeanalytics ?? new CommerceCustomerAnalyticsRepository($DB))
            ->snapshot($range['start'], $range['end']);
        $stats->nativecommerce = $native->has_activity();
        $stats->purchasecount = $native->purchasecount;
        $stats->successfulpurchasecount = $native->successfulpurchasecount;
        $stats->failedpurchasecount = $native->failedpurchasecount;
        $stats->fulfilledpurchasecount = $native->fulfilledpurchasecount;
        $stats->guestpurchasecount = $native->guestpurchasecount;
        $stats->attachedguestcount = $native->attachedguestcount;
        $stats->purchasesbytype = $native->purchasesbytype;
        $stats->purchasesbystatus = $native->purchasesbystatus;

        return $stats;
    }

    /**
     * @param array<string, DashboardRevenue> $revenues
     * @return array<string, array{
     *     currency: string,
     *     subscriptions: float,
     *     digital: float,
     *     total: float,
     *     formattedsubscriptions: string,
     *     formatteddigital: string,
     *     formattedtotal: string
     * }>
     */
    private function format_revenues(array $revenues): array {
        $result = [];

        foreach ($revenues as $currency => $revenue) {
            $result[$currency] = [
                'currency' => $currency,
                'subscriptions' => $revenue->subscriptions,
                'digital' => $revenue->digital,
                'total' => $revenue->total(),
                'formattedsubscriptions' =>
                    CurrencyFormatter::format(
                        $revenue->subscriptions,
                        $currency
                    ),
                'formatteddigital' =>
                    CurrencyFormatter::format(
                        $revenue->digital,
                        $currency
                    ),
                'formattedtotal' =>
                    CurrencyFormatter::format(
                        $revenue->total(),
                        $currency
                    ),
            ];
        }

        return $result;
    }

    /**
     * Calculate the same-period customer/trial ratio.
     *
     * This is intentionally not described as a cohort conversion rate.
     *
     * @param int $newtrials
     * @param int $newcustomers
     * @return float|null
     */
    private function calculate_trial_customer_ratio(
        int $newtrials,
        int $newcustomers
    ): ?float {
        if ($newtrials <= 0) {
            return null;
        }

        return round(
            ($newcustomers / $newtrials) * 100,
            1
        );
    }

}