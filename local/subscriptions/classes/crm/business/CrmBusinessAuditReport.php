<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable result of the CRM business-data audit.
 */
final class CrmBusinessAuditReport {

    /**
     * @param array<string, int> $subscriptionStatuses
     * @param array<string, int> $subscriptionPaymentStatuses
     * @param array<string, int> $digitalPaymentStatuses
     * @param string[] $subscriptionCurrencies
     * @param string[] $digitalCurrencies
     */
    public function __construct(
        public readonly int $subscriptions,
        public readonly int $trialSubscriptions,
        public readonly int $trialUsers,
        public readonly int $paidSubscriptions,
        public readonly int $paidCustomers,
        public readonly int $legacyPaidSubscriptions,
        public readonly int $unconfirmedSubscriptions,
        public readonly int $successfulSubscriptionPayments,
        public readonly int $unlinkedSuccessfulSubscriptionPayments,
        public readonly int $successfulDigitalPayments,
        public readonly int $digitalCustomers,
        public readonly int $trialPlanStatusMismatches,
        public readonly int $trialProviderMismatches,
        public readonly int $paidTrialSubscriptions,
        public readonly int $paidRequestsWithoutSubscription,
        public readonly int $paidRequestsWithoutPaymentDate,
        public readonly int $paidRequestsWithoutCurrency,
        public readonly int $digitalPaymentsWithoutPaymentDate,
        public readonly int $digitalPaymentsWithoutCurrency,
        public readonly int $subscriptionCurrencyMismatches,
        public readonly array $subscriptionStatuses,
        public readonly array $subscriptionPaymentStatuses,
        public readonly array $digitalPaymentStatuses,
        public readonly array $subscriptionCurrencies,
        public readonly array $digitalCurrencies
    ) {
    }

    /**
     * Whether important data inconsistencies were detected.
     */
    public function has_warnings(): bool {
        return $this->trialPlanStatusMismatches > 0
            || $this->trialProviderMismatches > 0
            || $this->paidTrialSubscriptions > 0
            || $this->paidRequestsWithoutPaymentDate > 0
            || $this->paidRequestsWithoutCurrency > 0
            || $this->digitalPaymentsWithoutPaymentDate > 0
            || $this->digitalPaymentsWithoutCurrency > 0
            || $this->subscriptionCurrencyMismatches > 0
            || $this->unconfirmedSubscriptions > 0;
    }

    /**
     * Export the report in a serialization-friendly format.
     */
    public function to_array(): array {
        return [
            'subscriptions' => $this->subscriptions,
            'trialsubscriptions' => $this->trialSubscriptions,
            'trialusers' => $this->trialUsers,
            'paidsubscriptions' => $this->paidSubscriptions,
            'paidcustomers' => $this->paidCustomers,
            'legacypaidsubscriptions' => $this->legacyPaidSubscriptions,
            'unconfirmedsubscriptions' => $this->unconfirmedSubscriptions,
            'successfulsubscriptionpayments' =>
                $this->successfulSubscriptionPayments,
            'unlinkedsuccessfulsubscriptionpayments' =>
                $this->unlinkedSuccessfulSubscriptionPayments,
            'successfuldigitalpayments' =>
                $this->successfulDigitalPayments,
            'digitalcustomers' => $this->digitalCustomers,
            'trialplanstatusmismatches' =>
                $this->trialPlanStatusMismatches,
            'trialprovidermismatches' =>
                $this->trialProviderMismatches,
            'paidtrialsubscriptions' =>
                $this->paidTrialSubscriptions,
            'paidrequestswithoutsubscription' =>
                $this->paidRequestsWithoutSubscription,
            'paidrequestswithoutpaymentdate' =>
                $this->paidRequestsWithoutPaymentDate,
            'paidrequestswithoutcurrency' =>
                $this->paidRequestsWithoutCurrency,
            'digitalpaymentswithoutpaymentdate' =>
                $this->digitalPaymentsWithoutPaymentDate,
            'digitalpaymentswithoutcurrency' =>
                $this->digitalPaymentsWithoutCurrency,
            'subscriptioncurrencymismatches' =>
                $this->subscriptionCurrencyMismatches,
            'subscriptionstatuses' =>
                $this->subscriptionStatuses,
            'subscriptionpaymentstatuses' =>
                $this->subscriptionPaymentStatuses,
            'digitalpaymentstatuses' =>
                $this->digitalPaymentStatuses,
            'subscriptioncurrencies' =>
                $this->subscriptionCurrencies,
            'digitalcurrencies' =>
                $this->digitalCurrencies,
        ];
    }
}