<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot;

/**
 * Compares a Commerce-domain snapshot with its legacy equivalent.
 */
final class CrmCommerceSnapshotComparator {

    public function compare(
        CrmCommerceCustomerSnapshot $commerce,
        CrmCommerceCustomerSnapshot $legacy
    ): CrmCommerceSnapshotComparison {
        if (
            $commerce->get_user_id()
            !== $legacy->get_user_id()
        ) {
            throw new \coding_exception(
                'CRM Commerce snapshots must belong to the same user.'
            );
        }

        $comparison = new CrmCommerceSnapshotComparison(
            $commerce->get_user_id()
        );

        $this->compare_value(
            $comparison,
            'subscription_count',
            $commerce->get_subscription_count(),
            $legacy->get_subscription_count()
        );

        $this->compare_value(
            $comparison,
            'digital_purchase_count',
            $commerce->get_digital_purchase_count(),
            $legacy->get_digital_purchase_count()
        );

        $this->compare_value(
            $comparison,
            'revenue_by_currency',
            $this->normalise_integer_map(
                $commerce->get_revenue_by_currency()
            ),
            $this->normalise_integer_map(
                $legacy->get_revenue_by_currency()
            )
        );

        $this->compare_value(
            $comparison,
            'provider_usage',
            $this->normalise_integer_map(
                $commerce->get_provider_usage()
            ),
            $this->normalise_integer_map(
                $legacy->get_provider_usage()
            )
        );

        $this->compare_value(
            $comparison,
            'status_usage',
            $this->normalise_integer_map(
                $commerce->get_status_usage()
            ),
            $this->normalise_integer_map(
                $legacy->get_status_usage()
            )
        );

        $this->compare_value(
            $comparison,
            'first_purchase_at',
            $commerce->get_first_purchase_at(),
            $legacy->get_first_purchase_at()
        );

        $this->compare_value(
            $comparison,
            'last_purchase_at',
            $commerce->get_last_purchase_at(),
            $legacy->get_last_purchase_at()
        );

        return $comparison;
    }

    private function compare_value(
        CrmCommerceSnapshotComparison $comparison,
        string $field,
        mixed $commercevalue,
        mixed $legacyvalue
    ): void {
        if ($commercevalue === $legacyvalue) {
            return;
        }

        $comparison->add_difference(
            new CrmCommerceSnapshotDifference(
                $field,
                $commercevalue,
                $legacyvalue
            )
        );
    }

    /**
     * @param array<string,int> $values
     * @return array<string,int>
     */
    private function normalise_integer_map(
        array $values
    ): array {
        $normalised = [];

        foreach ($values as $key => $value) {
            $key = strtolower(
                trim(
                    (string)$key
                )
            );

            if ($key === '') {
                $key = 'unknown';
            }

            $value = (int)$value;

            if ($value === 0) {
                continue;
            }

            $normalised[$key] =
                ($normalised[$key] ?? 0)
                + $value;
        }

        ksort($normalised);

        return $normalised;
    }
}