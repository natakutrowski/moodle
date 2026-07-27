<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable collection of entitlement grants planned for one purchase.
 */
final class CommerceEntitlementGrantPlan {
    /**
     * @param CommerceEntitlementGrant[] $grants
     */
    public function __construct(
        private readonly string $purchasereference,
        private readonly array $grants,
        private readonly int $plannedat
    ) {
        if (trim($purchasereference) === '') {
            throw new \coding_exception('An entitlement grant plan requires a purchase reference.');
        }

        foreach ($grants as $grant) {
            if (!$grant instanceof CommerceEntitlementGrant) {
                throw new \coding_exception('An entitlement grant plan contains an invalid grant.');
            }

            if ($grant->get_purchase_reference() !== trim($purchasereference)) {
                throw new \coding_exception('An entitlement grant belongs to another purchase.');
            }
        }

        if ($plannedat <= 0) {
            throw new \coding_exception('An entitlement grant plan timestamp must be positive.');
        }
    }

    public function get_purchase_reference(): string {
        return trim($this->purchasereference);
    }

    /**
     * @return CommerceEntitlementGrant[]
     */
    public function get_grants(): array {
        return $this->grants;
    }

    public function get_planned_at(): int {
        return $this->plannedat;
    }

    public function count(): int {
        return count($this->grants);
    }

    public function is_empty(): bool {
        return $this->grants === [];
    }

    /**
     * @return CommerceEntitlementGrant[]
     */
    public function find_by_type(string $type): array {
        $type = strtolower(trim($type));

        return array_values(array_filter(
            $this->grants,
            static fn(CommerceEntitlementGrant $grant): bool => $grant->get_type() === $type
        ));
    }
}
