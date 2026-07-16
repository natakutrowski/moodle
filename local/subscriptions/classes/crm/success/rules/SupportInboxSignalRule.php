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
 * Converts Inbox aggregates into support health signals.
 */
final class SupportInboxSignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'support_inbox_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return $metrics->has(
            SuccessMetricSource::INBOX,
            'support.inbox.conversation_count'
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

        $conversationcount = $this->integer_value(
            $metrics,
            'conversation_count'
        );

        if ($conversationcount <= 0) {
            return $signals;
        }

        $active = $this->integer_value(
            $metrics,
            'active_conversation_count'
        );

        $pending = $this->integer_value(
            $metrics,
            'pending_conversation_count'
        );

        $resolved = $this->integer_value(
            $metrics,
            'resolved_conversation_count'
        );

        $closed = $this->integer_value(
            $metrics,
            'closed_conversation_count'
        );

        $urgent = $this->integer_value(
            $metrics,
            'urgent_conversation_count'
        );

        $unread = $this->integer_value(
            $metrics,
            'unread_message_count'
        );

        $unassigned = $this->integer_value(
            $metrics,
            'unassigned_active_count'
        );

        $oldestage = $this->nullable_integer(
            $metrics,
            'oldest_active_age_days'
        );

        if ($active === 0 && ($resolved + $closed) > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'support.no_active_conversation',
                    12,
                    $resolved + $closed,
                    [
                        'active_conversation_count',
                        'resolved_conversation_count',
                        'closed_conversation_count',
                    ],
                    $detectedat
                )
            );
        }

        if ($active > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.active_conversations',
                    $active >= 3 ? -15 : -8,
                    $active,
                    ['active_conversation_count'],
                    $detectedat
                )
            );
        }

        if ($pending > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.pending_response',
                    $pending >= 2 ? -12 : -7,
                    $pending,
                    ['pending_conversation_count'],
                    $detectedat
                )
            );
        }

        if ($urgent > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.urgent_conversation',
                    $urgent >= 2 ? -28 : -20,
                    $urgent,
                    ['urgent_conversation_count'],
                    $detectedat
                )
            );
        }

        if ($unread > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.unread_messages',
                    $unread >= 5 ? -12 : -6,
                    $unread,
                    ['unread_message_count'],
                    $detectedat
                )
            );
        }

        if ($unassigned > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.unassigned_active_conversation',
                    $unassigned >= 2 ? -10 : -5,
                    $unassigned,
                    ['unassigned_active_count'],
                    $detectedat
                )
            );
        }

        if ($oldestage !== null && $oldestage >= 7) {
            $signals->add(
                $this->negative(
                    $userid,
                    'support.stale_active_conversation',
                    $oldestage >= 14 ? -18 : -10,
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
            SuccessMetricSource::INBOX,
            'support.inbox.' . $key
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
            SuccessMetricSource::INBOX,
            'support.inbox.' . $key
        );

        if ($metric === null || $metric->value === null) {
            return null;
        }

        return (int)$metric->value;
    }

    private function identity(
        string $key
    ): string {
        return
            SuccessMetricSource::INBOX .
            ':support.inbox.' .
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