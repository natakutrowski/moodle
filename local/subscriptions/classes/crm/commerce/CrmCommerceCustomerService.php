<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * CRM-facing read-only service over the unified Commerce domain.
 *
 * No renderer uses this service yet during Phase 7.93A.
 */
class CrmCommerceCustomerService {

    public function __construct(
        private readonly ?CommercePurchaseService $purchaseservice = null
    ) {
    }

    public function build_snapshot(
        int $userid,
        ?string $email = null
    ): CrmCommerceCustomerSnapshot {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'CRM Commerce customer identifier must be greater than zero.'
            );
        }

        $service = $this->purchaseservice
            ?? CommerceRuntimeFactory::create()
                ->purchases();

        $purchases = $service->get_customer_purchases(
            $userid,
            $email
        );

        $subscriptioncount = 0;
        $digitalpurchasecount = 0;
        $revenuebycurrency = [];
        $providerusage = [];
        $statususage = [];
        $firstpurchaseat = null;
        $lastpurchaseat = null;

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof CommercePurchase) {
                throw new \coding_exception(
                    'CrmCommerceCustomerService received an invalid purchase.'
                );
            }

            if ($purchase instanceof SubscriptionPurchase) {
                $subscriptioncount++;
            }

            if ($purchase instanceof DigitalPurchase) {
                $digitalpurchasecount++;
            }

            $this->collect_revenue(
                $purchase,
                $revenuebycurrency
            );

            $this->collect_provider(
                $purchase,
                $providerusage
            );

            $status = strtolower(
                trim(
                    $purchase->get_status()
                )
            );

            if ($status === '') {
                $status = 'unknown';
            }

            if (!isset($statususage[$status])) {
                $statususage[$status] = 0;
            }

            $statususage[$status]++;

            $createdat = $purchase->get_created_at();

            if ($createdat !== null) {
                if (
                    $firstpurchaseat === null
                    || $createdat < $firstpurchaseat
                ) {
                    $firstpurchaseat = $createdat;
                }

                if (
                    $lastpurchaseat === null
                    || $createdat > $lastpurchaseat
                ) {
                    $lastpurchaseat = $createdat;
                }
            }
        }

        ksort($revenuebycurrency);
        ksort($providerusage);
        ksort($statususage);

        return new CrmCommerceCustomerSnapshot(
            $userid,
            $purchases,
            $subscriptioncount,
            $digitalpurchasecount,
            $revenuebycurrency,
            $providerusage,
            $statususage,
            $firstpurchaseat,
            $lastpurchaseat
        );
    }

    /**
     * @param array<string,int> $revenuebycurrency
     */
    private function collect_revenue(
        CommercePurchase $purchase,
        array &$revenuebycurrency
    ): void {
        $payment = $purchase->get_payment();

        if (!$payment->is_successful()) {
            return;
        }

        $currency = $payment->get_currency();

        if (!isset($revenuebycurrency[$currency])) {
            $revenuebycurrency[$currency] = 0;
        }

        $revenuebycurrency[$currency] +=
            $payment->get_amount_minor();
    }

    /**
     * @param array<string,int> $providerusage
     */
    private function collect_provider(
        CommercePurchase $purchase,
        array &$providerusage
    ): void {
        $provider = $purchase
            ->get_payment()
            ->get_provider();

        $provider = $provider !== null
            ? strtolower(trim($provider))
            : 'unknown';

        if ($provider === '') {
            $provider = 'unknown';
        }

        if (!isset($providerusage[$provider])) {
            $providerusage[$provider] = 0;
        }

        $providerusage[$provider]++;
    }
}