<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable commercial price breakdown.
 *
 * initial - promotion = promoted
 * promoted - Trial = Trial-adjusted target
 * Trial-adjusted target - owned credit = final
 */
final class CommerceCommercialPriceBreakdown {
    public function __construct(
        private readonly string $currency,
        private readonly int $quantity,
        private readonly int $initialunitminor,
        private readonly int $promotionunitminor,
        private readonly int $promotedunitminor,
        private readonly int $trialdiscountunitminor,
        private readonly int $trialadjustedunitminor,
        private readonly int $ownedcreditunitminor,
        private readonly int $finalunitminor,
        private readonly int $productpromotionpercent = 0,
        private readonly int $trialdiscountpercent = 0,
        private readonly string $operation = '',
        private readonly ?string $upgradefromlabel = null,
        private readonly ?string $upgradetolabel = null,
        private readonly int $adjustmentdiscountunitminor = 0
    ) {
        if ($quantity <= 0) {
            throw new \coding_exception(
                'A commercial price breakdown requires a positive quantity.'
            );
        }

        foreach ([
            $initialunitminor,
            $promotionunitminor,
            $promotedunitminor,
            $trialdiscountunitminor,
            $trialadjustedunitminor,
            $ownedcreditunitminor,
            $finalunitminor,
            $adjustmentdiscountunitminor,
        ] as $amount) {
            if ($amount < 0) {
                throw new \coding_exception(
                    'Commercial price amounts cannot be negative.'
                );
            }
        }

        if ($initialunitminor - $promotionunitminor !== $promotedunitminor) {
            throw new \coding_exception(
                'Initial price minus promotion must equal promoted price.'
            );
        }

        if (
            $promotedunitminor - $trialdiscountunitminor
            !== $trialadjustedunitminor
        ) {
            throw new \coding_exception(
                'Promoted price minus Trial must equal Trial-adjusted price.'
            );
        }

        if (
            $trialadjustedunitminor - $ownedcreditunitminor - $adjustmentdiscountunitminor
            !== $finalunitminor
        ) {
            throw new \coding_exception(
                'Trial-adjusted price minus owned credit and checkout adjustment must equal final price.'
            );
        }
    }

    public function get_currency(): string {
        return strtoupper($this->currency);
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_initial_unit_minor(): int {
        return $this->initialunitminor;
    }

    public function get_promotion_unit_minor(): int {
        return $this->promotionunitminor;
    }

    public function get_promoted_unit_minor(): int {
        return $this->promotedunitminor;
    }

    public function get_trial_discount_unit_minor(): int {
        return $this->trialdiscountunitminor;
    }

    public function get_trial_adjusted_unit_minor(): int {
        return $this->trialadjustedunitminor;
    }

    public function get_owned_credit_unit_minor(): int {
        return $this->ownedcreditunitminor;
    }


    public function get_adjustment_discount_unit_minor(): int {
        return $this->adjustmentdiscountunitminor;
    }

    public function get_adjustment_discount_total_minor(): int {
        return $this->adjustmentdiscountunitminor * $this->quantity;
    }

    public function get_final_unit_minor(): int {
        return $this->finalunitminor;
    }

    /**
     * Compatibility getter: the payable Upgrade amount is the final amount.
     */
    public function get_upgrade_unit_minor(): int {
        return $this->finalunitminor;
    }

    public function get_initial_total_minor(): int {
        return $this->initialunitminor * $this->quantity;
    }

    public function get_promotion_total_minor(): int {
        return $this->promotionunitminor * $this->quantity;
    }

    public function get_promoted_total_minor(): int {
        return $this->promotedunitminor * $this->quantity;
    }

    public function get_trial_discount_total_minor(): int {
        return $this->trialdiscountunitminor * $this->quantity;
    }

    public function get_trial_adjusted_total_minor(): int {
        return $this->trialadjustedunitminor * $this->quantity;
    }

    public function get_owned_credit_total_minor(): int {
        return $this->ownedcreditunitminor * $this->quantity;
    }

    public function get_final_total_minor(): int {
        return $this->finalunitminor * $this->quantity;
    }

    public function get_upgrade_total_minor(): int {
        return $this->get_final_total_minor();
    }

    public function get_total_reduction_minor(): int {
        return $this->get_initial_total_minor()
            - $this->get_final_total_minor();
    }

    public function is_upgrade(): bool {
        return $this->operation === 'upgrade';
    }

    public function has_product_promotion(): bool {
        return $this->promotionunitminor > 0;
    }

    public function has_trial_discount(): bool {
        return $this->trialdiscountunitminor > 0;
    }

    /** @return array<string,mixed> */
    public function to_metadata(): array {
        return [
            'pricing_schema' => 'commercial_breakdown_v2',
            'pricing_currency' => $this->get_currency(),
            'pricing_quantity' => $this->quantity,
            'pricing_operation' => $this->operation,
            'pricing_initial_unit_minor' => $this->initialunitminor,
            'pricing_promotion_unit_minor' => $this->promotionunitminor,
            'pricing_promoted_unit_minor' => $this->promotedunitminor,
            'pricing_trial_discount_unit_minor' =>
                $this->trialdiscountunitminor,
            'pricing_trial_adjusted_unit_minor' =>
                $this->trialadjustedunitminor,
            'pricing_owned_credit_unit_minor' =>
                $this->ownedcreditunitminor,
            'pricing_adjustment_discount_unit_minor' => $this->adjustmentdiscountunitminor,
            'pricing_final_unit_minor' => $this->finalunitminor,
            'pricing_initial_total_minor' =>
                $this->get_initial_total_minor(),
            'pricing_promotion_total_minor' =>
                $this->get_promotion_total_minor(),
            'pricing_promoted_total_minor' =>
                $this->get_promoted_total_minor(),
            'pricing_trial_discount_total_minor' =>
                $this->get_trial_discount_total_minor(),
            'pricing_trial_adjusted_total_minor' =>
                $this->get_trial_adjusted_total_minor(),
            'pricing_owned_credit_total_minor' =>
                $this->get_owned_credit_total_minor(),
            'pricing_adjustment_discount_total_minor' =>
                $this->get_adjustment_discount_total_minor(),
            'pricing_final_total_minor' =>
                $this->get_final_total_minor(),
            'pricing_upgrade_unit_minor' => $this->get_upgrade_unit_minor(),
            'pricing_upgrade_total_minor' => $this->get_upgrade_total_minor(),
            'pricing_total_reduction_minor' =>
                $this->get_total_reduction_minor(),
            'pricing_product_promotion_percent' =>
                $this->productpromotionpercent,
            'pricing_trial_discount_percent' =>
                $this->trialdiscountpercent,
            'pricing_upgrade_from_label' => $this->upgradefromlabel,
            'pricing_upgrade_to_label' => $this->upgradetolabel,
        ];
    }
}
