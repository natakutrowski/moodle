<?php

namespace local_subscriptions\crm\success\rules;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessSignalRuleInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\domain\SuccessSignalCategory;
use local_subscriptions\crm\success\domain\SuccessSignalPolarity;
use local_subscriptions\crm\success\signals\SuccessSignal;
use local_subscriptions\crm\success\signals\SuccessSignalCollection;

/**
 * Converts targeted Work Item aggregates into support health signals.
 */
final class WorkItemSuccessSignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'work_item_success_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return $metrics->has(
            SuccessMetricSource::WORK_ITEMS,
            'support.work_items.item_count'
        );
    }

    public function evaluate(
        SuccessMetricCollection $metrics,
        int $detectedat
    ): SuccessSignalCollection {
        $userid = $metrics->userid();

        if ($userid === null) {
            return new SuccessSignalCollection();
        }

        $signals = new SuccessSignalCollection();

        $itemcount = $this->integer_value(
            $metrics,
            'item_count'
        );

        /*
         * No Work Item means there is no Work Management information for
         * this user. It does not imply excellent support health.
         */
        if ($itemcount <= 0) {
            return $signals;
        }

        $active = $this->integer_value(
            $metrics,
            'active_count'
        );

        $blocked = $this->integer_value(
            $metrics,
            'blocked_count'
        );

        $urgent = $this->integer_value(
            $metrics,
            'urgent_active_count'
        );

        $overdue = $this->integer_value(
            $metrics,
            'overdue_count'
        );

        $resolved = $this->integer_value(
            $metrics,
            'resolved_count'
        );

        $closed = $this->integer_value(
            $metrics,
            'closed_count'
        );

        $unassigned = $this->integer_value(
            $metrics,
            'unassigned_active_count'
        );

        $oldestage = $this->nullable_integer(
            $metrics,
            'oldest_active_age_days'
        );

        if (
            $active === 0 &&
            ($resolved + $closed) > 0
        ) {
            $signals->add(
                $this->positive(
                    $userid,
                    'support.work_items_all_resolved',
                    12,
                    $resolved + $closed,
                    [
                        'active_count',
                        'resolved_count',
                        'closed_count',
                    ],
                    $detectedat
                )
            );
        }

        if ($active > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_active',
                    $active >= 4 ? -15 : -8,
                    $active,
                    ['active_count'],
                    $detectedat
                )
            );
        }

        if ($blocked > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_blocked',
                    $blocked >= 2 ? -18 : -10,
                    $blocked,
                    ['blocked_count'],
                    $detectedat
                )
            );
        }

        if ($urgent > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_urgent',
                    $urgent >= 2 ? -28 : -20,
                    $urgent,
                    ['urgent_active_count'],
                    $detectedat
                )
            );
        }

        if ($overdue > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_overdue',
                    $overdue >= 3 ? -22 : -12,
                    $overdue,
                    ['overdue_count'],
                    $detectedat
                )
            );
        }

        if ($unassigned > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_unassigned',
                    $unassigned >= 3 ? -12 : -6,
                    $unassigned,
                    ['unassigned_active_count'],
                    $detectedat
                )
            );
        }

        if (
            $oldestage !== null &&
            $oldestage >= 7
        ) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.work_items_stale',
                    $oldestage >= 30
                        ? -20
                        : ($oldestage >= 14 ? -14 : -8),
                    $oldestage,
                    [
                        'oldest_active_at',
                        'oldest_active_age_days',
                    ],
                    $detectedat
                )
            );
        }

        return $signals;
    }

    private function integer_value(
        SuccessMetricCollection $metrics,
        string $key
    ): int {
        $metric = $metrics->get(
            SuccessMetricSource::WORK_ITEMS,
            'support.work_items.' . $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function nullable_integer(
        SuccessMetricCollection $metrics,
        string $key
    ): ?int {
        $metric = $metrics->get(
            SuccessMetricSource::WORK_ITEMS,
            'support.work_items.' . $key
        );

        if (
            $metric === null ||
            $metric->value === null
        ) {
            return null;
        }

        return (int)$metric->value;
    }

    private function identity(
        string $key
    ): string {
        return
            SuccessMetricSource::WORK_ITEMS .
            ':support.work_items.' .
            $key;
    }

    /**
     * @param string[] $metrickeys
     */
    private function positive(
        int $userid,
        string $key,
        int $weight,
        int|float $value,
        array $metrickeys,
        int $detectedat
    ): SuccessSignal {
        return new SuccessSignal(
            $userid,
            $key,
            SuccessSignalCategory::SUPPORT,
            SuccessSignalPolarity::POSITIVE,
            $weight,
            $value,
            array_map(
                fn(string $metrickey): string =>
                    $this->identity($metrickey),
                $metrickeys
            ),
            $detectedat
        );
    }

    /**
     * @param string[] $metrickeys
     */
    private function negative(
        int $userid,
        string $key,
        int $weight,
        int|float $value,
        array $metrickeys,
        int $detectedat
    ): SuccessSignal {
        return new SuccessSignal(
            $userid,
            $key,
            SuccessSignalCategory::SUPPORT,
            SuccessSignalPolarity::NEGATIVE,
            $weight,
            $value,
            array_map(
                fn(string $metrickey): string =>
                    $this->identity($metrickey),
                $metrickeys
            ),
            $detectedat
        );
    }
}