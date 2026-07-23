<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;

/**
 * Read-only Commerce snapshot for one CRM customer.
 */
final class CrmCommerceCustomerSnapshot {

    /**
     * @param CommercePurchase[] $purchases
     * @param array<string,int> $revenuebycurrency Amounts in minor units.
     * @param array<string,int> $providerusage
     * @param array<string,int> $statususage
     */
    public function __construct(
        private readonly int $userid,
        private readonly array $purchases,
        private readonly int $subscriptioncount,
        private readonly int $digitalpurchasecount,
        private readonly array $revenuebycurrency,
        private readonly array $providerusage,
        private readonly array $statususage,
        private readonly ?int $firstpurchaseat,
        private readonly ?int $lastpurchaseat,
        private readonly string $source =
            CrmCommerceSnapshotSource::COMMERCE_DOMAIN
    ) {
        if ($userid <= 0) {
            throw new \coding_exception(
                'A CRM Commerce snapshot user identifier must be positive.'
            );
        }

        if (
            !CrmCommerceSnapshotSource::is_valid(
                $source
            )
        ) {
            throw new \coding_exception(
                'Unsupported CRM Commerce snapshot source: ' .
                $source
            );
        }        

        foreach ($purchases as $purchase) {
            if (!$purchase instanceof CommercePurchase) {
                throw new \coding_exception(
                    'A CRM Commerce snapshot can only contain CommercePurchase objects.'
                );
            }
        }
    }

    public function get_user_id(): int {
        return $this->userid;
    }

    /**
     * @return CommercePurchase[]
     */
    public function get_purchases(): array {
        return $this->purchases;
    }

    public function get_purchase_count(): int {
        return count($this->purchases);
    }

    public function get_subscription_count(): int {
        return $this->subscriptioncount;
    }

    public function get_digital_purchase_count(): int {
        return $this->digitalpurchasecount;
    }

    /**
     * Amounts are expressed in minor currency units.
     *
     * @return array<string,int>
     */
    public function get_revenue_by_currency(): array {
        return $this->revenuebycurrency;
    }

    /**
     * @return array<string,int>
     */
    public function get_provider_usage(): array {
        return $this->providerusage;
    }

    /**
     * @return array<string,int>
     */
    public function get_status_usage(): array {
        return $this->statususage;
    }

    public function get_first_purchase_at(): ?int {
        return $this->firstpurchaseat;
    }

    public function get_last_purchase_at(): ?int {
        return $this->lastpurchaseat;
    }

    public function has_purchases(): bool {
        return $this->purchases !== [];
    }

    public function has_used_provider(
        string $provider
    ): bool {
        $provider = strtolower(
            trim($provider)
        );

        return isset(
            $this->providerusage[$provider]
        );
    }

    public function get_source(): string {
        return $this->source;
    }

    public function uses_legacy_fallback(): bool {
        return $this->source ===
            CrmCommerceSnapshotSource::LEGACY_FALLBACK;
    }

}