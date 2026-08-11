<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads the immutable pricing snapshot persisted with a Native purchase.
 *
 * This presenter never consults current catalogue prices. Invoices, customer
 * order details and CRM audit pages must explain what happened at purchase
 * time, even if catalogue prices or promotions later change.
 */
final class CommercePersistedCommercialPricingPresenter {
    /**
     * @param array<string,mixed> $metadata
     * @return array<string,int|bool|string>
     */
    public function item(
        array $metadata,
        int $grossminor,
        int $discountminor,
        int $netminor,
        int $quantity = 1
    ): array {
        $quantity = max(1, $quantity);

        $initial = $this->amount(
            $metadata,
            [
                'pricing_initial_total_minor',
                'locked_list_total_minor',
            ],
            max($grossminor, $netminor)
        );
        $promotion = $this->amount(
            $metadata,
            [
                'pricing_promotion_total_minor',
                'locked_product_promotion_minor',
            ]
        );
        $trial = $this->amount(
            $metadata,
            [
                'pricing_trial_discount_total_minor',
                'locked_trial_discount_minor',
            ]
        );
        $credit = $this->amount(
            $metadata,
            ['pricing_owned_credit_total_minor']
        );
        $final = $this->amount(
            $metadata,
            [
                'pricing_final_total_minor',
                'locked_total_minor',
            ],
            $netminor
        );

        // Legacy Native purchases may only expose gross/discount/net.
        $knownreductions = $promotion + $trial + $credit;
        $totalreduction = max(
            0,
            $this->amount(
                $metadata,
                [
                    'pricing_total_reduction_minor',
                    'locked_total_discount_minor',
                ],
                max($discountminor, $initial - $final)
            )
        );
        $otherdiscount = max(
            0,
            $totalreduction - $knownreductions
        );

        $promotionpercent = $initial > 0 && $promotion > 0
            ? (int)round(($promotion * 100) / $initial)
            : 0;
        $trialbase = max(0, $initial - $promotion);
        $trialpercent = $trialbase > 0 && $trial > 0
            ? (int)round(($trial * 100) / $trialbase)
            : 0;
        $operation = strtolower(trim((string)(
            $metadata['pricing_operation']
            ?? $metadata['commerceoperation']
            ?? $metadata['operation']
            ?? ''
        )));
        $fromlabel = trim((string)(
            $metadata['pricing_upgrade_from_label']
            ?? $metadata['upgradefromlabel']
            ?? ''
        ));
        $tolabel = trim((string)(
            $metadata['pricing_upgrade_to_label']
            ?? $metadata['upgradetolabel']
            ?? ''
        ));

        return [
            'haspricing' => $totalreduction > 0,
            'haspromotion' => $promotion > 0,
            'hastrial' => $trial > 0,
            'hascredit' => $credit > 0,
            'hasotherdiscount' => $otherdiscount > 0,
            'initialminor' => $initial,
            'promotionminor' => $promotion,
            'trialminor' => $trial,
            'creditminor' => $credit,
            'otherdiscountminor' => $otherdiscount,
            'totalreductionminor' => $totalreduction,
            'finalminor' => $final,
            'quantity' => $quantity,
            'promotionpercent' => $promotionpercent,
            'haspromotionpercent' => $promotionpercent > 0,
            'trialpercent' => $trialpercent,
            'hastrialpercent' => $trialpercent > 0,
            'operation' => $operation,
            'isupgrade' => $operation === 'upgrade',
            'fromlabel' => $fromlabel,
            'tolabel' => $tolabel,
            'hasupgradepath' => $operation === 'upgrade'
                && $fromlabel !== ''
                && $tolabel !== '',
        ];
    }

    /**
     * @param array<string,mixed> $metadata
     * @param array<int,array<string,int|bool|string>> $items
     * @return array<string,int|bool>
     */
    public function order(
        array $metadata,
        array $items,
        int $paidminor
    ): array {
        $iteminitial = array_sum(array_map(
            static fn(array $item): int =>
                (int)$item['initialminor'],
            $items
        ));
        $itempromotion = array_sum(array_map(
            static fn(array $item): int =>
                (int)$item['promotionminor'],
            $items
        ));
        $itemtrial = array_sum(array_map(
            static fn(array $item): int =>
                (int)$item['trialminor'],
            $items
        ));
        $itemcredit = array_sum(array_map(
            static fn(array $item): int =>
                (int)$item['creditminor'],
            $items
        ));
        $itemother = array_sum(array_map(
            static fn(array $item): int =>
                (int)$item['otherdiscountminor'],
            $items
        ));

        $initial = $this->amount(
            $metadata,
            ['cart_list_total_minor'],
            max($iteminitial, $paidminor)
        );
        $promotion = $this->amount(
            $metadata,
            ['cart_product_promotion_minor'],
            $itempromotion
        );
        $trial = $this->amount(
            $metadata,
            ['cart_trial_discount_minor'],
            $itemtrial
        );
        $credit = $this->amount(
            $metadata,
            ['cart_owned_credit_minor'],
            $itemcredit
        );
        $adjustment = $this->amount(
            $metadata,
            ['cart_adjustment_discount_minor'],
            $itemother
        );
        $totalreduction = $this->amount(
            $metadata,
            ['cart_discount_minor'],
            max(0, $initial - $paidminor)
        );

        // Ensure the displayed decomposition reconciles exactly with paid total.
        $explained = $promotion + $trial + $credit + $adjustment;
        $unclassified = max(
            0,
            $totalreduction - $explained
        );
        $adjustment += $unclassified;

        return [
            'haspricing' => $totalreduction > 0,
            'haspromotion' => $promotion > 0,
            'hastrial' => $trial > 0,
            'hascredit' => $credit > 0,
            'hasadjustment' => $adjustment > 0,
            'initialminor' => $initial,
            'promotionminor' => $promotion,
            'trialminor' => $trial,
            'creditminor' => $credit,
            'adjustmentminor' => $adjustment,
            'totalreductionminor' => max(
                0,
                $initial - $paidminor
            ),
            'paidminor' => $paidminor,
        ];
    }

    /**
     * @param array<string,mixed> $metadata
     * @param string[] $keys
     */
    private function amount(
        array $metadata,
        array $keys,
        int $fallback = 0
    ): int {
        foreach ($keys as $key) {
            $value = $metadata[$key] ?? null;
            if (is_numeric($value)) {
                return max(0, (int)$value);
            }
        }

        return max(0, $fallback);
    }
}
