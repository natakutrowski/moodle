<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** CRM customer read model backed exclusively by native Commerce tables. */
final class NativeCrmCommerceCustomerService {
    public function __construct(
        private readonly ?CommercePurchaseSqlRepository $repository = null,
        private readonly ?NativeCrmCommercePurchaseMapper $mapper = null
    ) {}

    public function build_snapshot(int $userid, ?string $email = null): CrmCommerceCustomerSnapshot {
        $repository = $this->repository ?? CommercePurchaseSqlRepositoryFactory::create();
        $mapper = $this->mapper ?? new NativeCrmCommercePurchaseMapper();
        $purchases = array_map([$mapper, 'map'], $repository->find_by_customer($userid, $email));
        return $this->aggregate($userid, $purchases);
    }

    /** @param CommercePurchase[] $purchases */
    private function aggregate(int $userid, array $purchases): CrmCommerceCustomerSnapshot {
        $subscriptions = 0; $digital = 0; $revenue = []; $providers = []; $statuses = [];
        $first = null; $last = null;
        foreach ($purchases as $purchase) {
            $purchase->get_type() === 'subscription' ? $subscriptions++ : ($purchase->get_type() === 'digital' ? $digital++ : null);
            $status = strtolower(trim($purchase->get_status())) ?: 'unknown';
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            $payment = $purchase->get_payment();
            $provider = strtolower(trim((string)($payment->get_provider() ?? 'unknown'))) ?: 'unknown';
            $providers[$provider] = ($providers[$provider] ?? 0) + 1;
            if ($payment->is_successful()) {
                $currency = $payment->get_currency();
                $revenue[$currency] = ($revenue[$currency] ?? 0) + $payment->get_amount_minor();
            }
            $created = $purchase->get_created_at();
            if ($created !== null) { $first = $first === null ? $created : min($first, $created); $last = $last === null ? $created : max($last, $created); }
        }
        ksort($revenue); ksort($providers); ksort($statuses);
        return new CrmCommerceCustomerSnapshot($userid, $purchases, $subscriptions, $digital, $revenue, $providers, $statuses, $first, $last, CrmCommerceSnapshotSource::NATIVE_RUNTIME);
    }
}
