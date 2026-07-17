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
 * Converts subscription and payment metrics into commercial and loyalty
 * signals.
 *
 * Monetary amounts are deliberately not combined across currencies.
 */
final class CommercialLoyaltySignalRule implements
    SuccessSignalRuleInterface {

    public function key(): string {
        return 'commercial_loyalty_signals';
    }

    public function supports(
        SuccessMetricCollection $metrics
    ): bool {
        return
            $metrics->has(
                SuccessMetricSource::SUBSCRIPTIONS,
                'commercial.subscription_count'
            ) ||
            $metrics->has(
                SuccessMetricSource::CRM,
                'loyalty.customer_age_days'
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

        $this->evaluate_commercial(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        $this->evaluate_loyalty(
            $signals,
            $metrics,
            $userid,
            $detectedat
        );

        return $signals;
    }

    private function evaluate_commercial(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $active = $this->commercial_integer(
            $metrics,
            'active_subscription_count'
        );

        $trial = $this->commercial_integer(
            $metrics,
            'trial_subscription_count'
        );

        $successfulpayments = $this->commercial_integer(
            $metrics,
            'successful_subscription_payment_count'
        );

        $successfuldigital = $this->commercial_integer(
            $metrics,
            'successful_digital_purchase_count'
        );

        $recentpayments = $this->commercial_integer(
            $metrics,
            'successful_subscription_payments_30d'
        );

        $recentdigital = $this->commercial_integer(
            $metrics,
            'digital_purchases_30d'
        );

        $failed30d = $this->commercial_integer(
            $metrics,
            'failed_subscription_payments_30d'
        );

        $pending = $this->commercial_integer(
            $metrics,
            'pending_subscription_payment_count'
        );

        if ($active > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.active_customer',
                    SuccessSignalCategory::COMMERCIAL,
                    20,
                    $active,
                    [
                        $this->commercial_identity(
                            'active_subscription_count'
                        ),
                    ],
                    $detectedat
                )
            );
        } else if ($trial > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.active_trial',
                    SuccessSignalCategory::COMMERCIAL,
                    8,
                    $trial,
                    [
                        $this->commercial_identity(
                            'trial_subscription_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($successfulpayments >= 2) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.repeat_subscription_payments',
                    SuccessSignalCategory::COMMERCIAL,
                    12,
                    $successfulpayments,
                    [
                        $this->commercial_identity(
                            'successful_subscription_payment_count'
                        ),
                    ],
                    $detectedat
                )
            );
        } else if ($successfulpayments === 1) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.successful_subscription_payment',
                    SuccessSignalCategory::COMMERCIAL,
                    7,
                    $successfulpayments,
                    [
                        $this->commercial_identity(
                            'successful_subscription_payment_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($successfuldigital >= 2) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.repeat_digital_purchases',
                    SuccessSignalCategory::COMMERCIAL,
                    10,
                    $successfuldigital,
                    [
                        $this->commercial_identity(
                            'successful_digital_purchase_count'
                        ),
                    ],
                    $detectedat
                )
            );
        } else if ($successfuldigital === 1) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.successful_digital_purchase',
                    SuccessSignalCategory::COMMERCIAL,
                    6,
                    $successfuldigital,
                    [
                        $this->commercial_identity(
                            'successful_digital_purchase_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if (($recentpayments + $recentdigital) > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'commercial.recent_purchase_activity',
                    SuccessSignalCategory::COMMERCIAL,
                    8,
                    $recentpayments + $recentdigital,
                    [
                        $this->commercial_identity(
                            'successful_subscription_payments_30d'
                        ),
                        $this->commercial_identity(
                            'digital_purchases_30d'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($failed30d > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'commercial.recent_failed_payments',
                    SuccessSignalCategory::COMMERCIAL,
                    $failed30d >= 2 ? -25 : -15,
                    $failed30d,
                    [
                        $this->commercial_identity(
                            'failed_subscription_payments_30d'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($pending > 0) {
            $signals->add(
                $this->negative(
                    $userid,
                    'commercial.pending_payments',
                    SuccessSignalCategory::COMMERCIAL,
                    $pending >= 2 ? -12 : -7,
                    $pending,
                    [
                        $this->commercial_identity(
                            'pending_subscription_payment_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }
    }

    private function evaluate_loyalty(
        SuccessSignalCollection $signals,
        SuccessMetricCollection $metrics,
        int $userid,
        int $detectedat
    ): void {
        $customeragedays = $this->loyalty_nullable_integer(
            $metrics,
            'customer_age_days'
        );

        $replacedsubscriptions = $this->loyalty_integer(
            $metrics,
            'replaced_subscription_count'
        );

        $remainingdays = $this->commercial_integer(
            $metrics,
            'active_subscription_remaining_days'
        );

        $active = $this->commercial_integer(
            $metrics,
            'active_subscription_count'
        );

        $expired = $this->commercial_integer(
            $metrics,
            'expired_subscription_count'
        );

        $cancelled = $this->commercial_integer(
            $metrics,
            'cancelled_subscription_count'
        );

        $successfulpayments = $this->commercial_integer(
            $metrics,
            'successful_subscription_payment_count'
        );

        if ($customeragedays !== null) {
            if ($customeragedays >= 365) {
                $signals->add(
                    $this->positive(
                        $userid,
                        'loyalty.customer_over_one_year',
                        SuccessSignalCategory::LOYALTY,
                        18,
                        $customeragedays,
                        [
                            $this->loyalty_identity(
                                'customer_age_days'
                            ),
                        ],
                        $detectedat
                    )
                );
            } else if ($customeragedays >= 180) {
                $signals->add(
                    $this->positive(
                        $userid,
                        'loyalty.customer_over_six_months',
                        SuccessSignalCategory::LOYALTY,
                        12,
                        $customeragedays,
                        [
                            $this->loyalty_identity(
                                'customer_age_days'
                            ),
                        ],
                        $detectedat
                    )
                );
            } else if ($customeragedays >= 90) {
                $signals->add(
                    $this->positive(
                        $userid,
                        'loyalty.customer_over_three_months',
                        SuccessSignalCategory::LOYALTY,
                        7,
                        $customeragedays,
                        [
                            $this->loyalty_identity(
                                'customer_age_days'
                            ),
                        ],
                        $detectedat
                    )
                );
            }
        }

        if ($replacedsubscriptions > 0) {
            $signals->add(
                $this->positive(
                    $userid,
                    'loyalty.subscription_evolution',
                    SuccessSignalCategory::LOYALTY,
                    min(15, 8 + (($replacedsubscriptions - 1) * 3)),
                    $replacedsubscriptions,
                    [
                        $this->loyalty_identity(
                            'replaced_subscription_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($successfulpayments >= 2) {
            $signals->add(
                $this->positive(
                    $userid,
                    'loyalty.repeat_customer',
                    SuccessSignalCategory::LOYALTY,
                    min(18, 8 + (($successfulpayments - 2) * 2)),
                    $successfulpayments,
                    [
                        $this->commercial_identity(
                            'successful_subscription_payment_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if ($active > 0 && $remainingdays >= 30) {
            $signals->add(
                $this->positive(
                    $userid,
                    'loyalty.stable_active_access',
                    SuccessSignalCategory::LOYALTY,
                    6,
                    $remainingdays,
                    [
                        $this->commercial_identity(
                            'active_subscription_count'
                        ),
                        $this->commercial_identity(
                            'active_subscription_remaining_days'
                        ),
                    ],
                    $detectedat
                )
            );
        }

        if (
            $active === 0 &&
            ($expired > 0 || $cancelled > 0)
        ) {
            $signals->add(
                $this->negative(
                    $userid,
                    'loyalty.no_current_access',
                    SuccessSignalCategory::LOYALTY,
                    -15,
                    $expired + $cancelled,
                    [
                        $this->commercial_identity(
                            'active_subscription_count'
                        ),
                        $this->commercial_identity(
                            'expired_subscription_count'
                        ),
                        $this->commercial_identity(
                            'cancelled_subscription_count'
                        ),
                    ],
                    $detectedat
                )
            );
        }
    }

    private function commercial_integer(
        SuccessMetricCollection $metrics,
        string $key
    ): int {
        $metric = $metrics->get(
            SuccessMetricSource::SUBSCRIPTIONS,
            'commercial.' . $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function loyalty_integer(
        SuccessMetricCollection $metrics,
        string $key
    ): int {
        $metric = $metrics->get(
            SuccessMetricSource::CRM,
            'loyalty.' . $key
        );

        return $metric !== null
            ? (int)$metric->value
            : 0;
    }

    private function loyalty_nullable_integer(
        SuccessMetricCollection $metrics,
        string $key
    ): ?int {
        $metric = $metrics->get(
            SuccessMetricSource::CRM,
            'loyalty.' . $key
        );

        if ($metric === null || $metric->value === null) {
            return null;
        }

        return (int)$metric->value;
    }

    private function commercial_identity(
        string $key
    ): string {
        return
            SuccessMetricSource::SUBSCRIPTIONS .
            ':commercial.' .
            $key;
    }

    private function loyalty_identity(
        string $key
    ): string {
        return
            SuccessMetricSource::CRM .
            ':loyalty.' .
            $key;
    }

    /**
     * @param string[] $metricidentities
     */
    private function positive(
        int $userid,
        string $key,
        string $category,
        int $weight,
        int|float $value,
        array $metricidentities,
        int $detectedat
    ): SuccessSignal {
        return new SuccessSignal(
            $userid,
            $key,
            $category,
            SuccessSignalPolarity::POSITIVE,
            $weight,
            $value,
            $metricidentities,
            $detectedat
        );
    }

    /**
     * @param string[] $metricidentities
     */
    private function negative(
        int $userid,
        string $key,
        string $category,
        int $weight,
        int|float $value,
        array $metricidentities,
        int $detectedat
    ): SuccessSignal {
        return new SuccessSignal(
            $userid,
            $key,
            $category,
            SuccessSignalPolarity::NEGATIVE,
            $weight,
            $value,
            $metricidentities,
            $detectedat
        );
    }
}