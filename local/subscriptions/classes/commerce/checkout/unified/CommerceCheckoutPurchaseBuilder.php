<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestStatus;
use local_subscriptions\commerce\domain\value\CommercePurchaseId;
use local_subscriptions\commerce\domain\value\CommercePurchaseReference;
use local_subscriptions\commerce\pricing\CommerceCommercialPriceResolver;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSalePolicy;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinEligibilityService;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinOperation;
use local_subscriptions\commerce\education\promotionjoin\CommercePedagogicalPromotionJoinPricingService;

/** Freezes a checkout summary into a provider-independent pending purchase request. */
final class CommerceCheckoutPurchaseBuilder {
    public function build(
        CommerceCheckoutSummary $summary,
        CommerceCustomer $customer,
        ?string $reference = null
    ): CommercePurchaseRequest {
        if (!$summary->is_valid()) {
            $codes = array_map(
                static fn(CommerceCheckoutValidationIssue $issue): string => $issue->get_code(),
                $summary->get_validation()->get_issues()
            );
            throw new \RuntimeException(
                'An invalid checkout summary cannot become a purchase. Issues: ' . implode(', ', $codes)
            );
        }

        $reference ??= CommercePurchaseReference::from_purchase_id(CommercePurchaseId::generate())->get_value();
        $calculateditems = $summary->get_cart_snapshot()->get_items();
        $allocations = $this->allocate_total(
            $calculateditems,
            $summary->get_total_minor()
        );
        $items = [];
        $listtotalminor = 0;
        $productpromotionminor = 0;
        $trialdiscountminor = 0;
        $ownedcreditminor = 0;
        $upgradetotalminor = 0;

        $pedagogicalsalepolicy = CommercePedagogicalSalePolicy::create($GLOBALS['DB']);

        foreach ($calculateditems as $index => $calculated) {
            $cartitem = $calculated->get_item();
            $pedagogicalsalepolicy->assert_product_available(
                $cartitem->get_product_sku(),
                time(),
                $summary->get_cart_snapshot()->get_cart()->get_uuid()
            );
            $quantity = $cartitem->get_quantity();
            $linetotal = $allocations[$index];
            $unitamount = intdiv($linetotal, $quantity);
            $remainder = $linetotal - ($unitamount * $quantity);
            // Quantity splitting is deferred to H3. Current catalogue policies use quantity 1 for sellable products.
            if ($remainder !== 0) {
                throw new \RuntimeException('Checkout allocation requires an indivisible per-unit amount.');
            }

            $promotionjoinmetadata = $this->promotion_join_checkout_metadata(
                $summary,
                $customer,
                $calculated,
                $linetotal
            );

            $pricing = (new CommerceCommercialPriceResolver($GLOBALS['DB']))->resolve(
                $calculated,
                $summary->get_currency(),
                $linetotal,
                (int)($customer->get_user_id() ?? 0)
            );
            $listtotalminor += $pricing->get_initial_total_minor();
            $ownedcreditminor += $pricing->get_owned_credit_total_minor();
            $upgradetotalminor += $pricing->get_upgrade_total_minor();
            $productpromotionminor += $pricing->get_promotion_total_minor();
            $trialdiscountminor += $pricing->get_trial_discount_total_minor();

            $items[] = new CommercePurchaseRequestItem(
                new CommerceItem(
                    $this->map_type($calculated->get_product_type()),
                    $cartitem->get_product_sku(),
                    $calculated->get_name(),
                    null,
                    ['priceid' => $cartitem->get_price_id()]
                ),
                $quantity,
                $unitamount,
                $summary->get_currency(),
                array_merge(
                    $cartitem->get_metadata(),
                    $promotionjoinmetadata,
                    $pricing->to_metadata(),
                    [
                        'priceid' => $cartitem->get_price_id(),
                        'locked_subtotal_minor' =>
                            $calculated->get_subtotal()->get_amount_minor(),
                        'locked_total_minor' => $linetotal,
                        'locked_list_unit_minor' => $pricing->get_initial_unit_minor(),
                        'locked_promoted_unit_minor' => $pricing->get_promoted_unit_minor(),
                        'locked_payable_unit_minor' => $unitamount,
                        'locked_list_total_minor' => $pricing->get_initial_total_minor(),
                        'locked_product_promotion_minor' => $pricing->get_promotion_total_minor(),
                        'locked_trial_discount_minor' => $pricing->get_trial_discount_total_minor(),
                        'locked_total_discount_minor' => $pricing->get_total_reduction_minor(),
                        'commerceoperation' => strtolower(trim((string)(
                            $cartitem->get_metadata()['operation'] ?? ''
                        ))),
                    ]
                )
            );
        }

        return new CommercePurchaseRequest(
            $reference,
            $customer,
            $items,
            CommercePurchaseRequestStatus::PAYMENT_PENDING,
            $summary->get_context()->get_provider(),
            $summary->get_context()->get_return_url(),
            $summary->get_context()->get_cancel_url(),
            array_merge($summary->get_context()->get_metadata(), [
                'checkout_created_at' => $summary->get_created_at(),
                'cart_uuid' => $summary->get_cart_snapshot()->get_cart()->get_uuid(),
                'cart_customerid' => $summary->get_cart_snapshot()->get_cart()->get_customer_id(),
                'cart_currency' => $summary->get_cart_snapshot()->get_cart()->get_currency(),
                'cart_subtotal_minor' => $summary->get_subtotal_minor(),
                'cart_list_total_minor' => $listtotalminor,
                'cart_owned_credit_minor' => $ownedcreditminor,
                'cart_upgrade_total_minor' => $upgradetotalminor,
                'cart_product_promotion_minor' => $productpromotionminor,
                'cart_trial_discount_minor' => $trialdiscountminor,
                'cart_adjustment_discount_minor' =>
                    $summary->get_discount_minor(),
                'pricing_schema' => 'commercial_breakdown_v1',
                'cart_discount_minor' => max(
                    0,
                    $listtotalminor - $summary->get_total_minor()
                ),
                'cart_tax_minor' => $summary->get_tax_minor(),
                'cart_total_minor' => $summary->get_total_minor(),
                'promotion_codes' => array_values(array_filter(array_map(
                    static fn($adjustment): ?string => $adjustment->get_code(),
                    $summary->get_cart_snapshot()->get_promotion_adjustments()
                ))),
            ]),
            time()
        );
    }

    /** @return array<string,int|string> */
    private function promotion_join_checkout_metadata(
        CommerceCheckoutSummary $summary,
        CommerceCustomer $customer,
        object $calculated,
        int $linetotalminor
    ): array {
        $cartitem = $calculated->get_item();
        $metadata = $cartitem->get_metadata();
        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
        if ($operation !== CommercePedagogicalPromotionJoinOperation::OPERATION) {
            return [];
        }

        $cart = $summary->get_cart_snapshot()->get_cart();
        $userid = $cart->get_customer_id();
        if (
            $userid <= 0
            || (int)($customer->get_user_id() ?? 0) !== $userid
            || (int)($metadata['promotion_join_user_id'] ?? 0) !== $userid
            || $cartitem->get_quantity() !== 1
        ) {
            throw new \RuntimeException('Promotion join checkout owner identity is no longer valid.');
        }

        $now = time();
        $eligibility = CommercePedagogicalPromotionJoinEligibilityService::create($GLOBALS['DB'])->resolve(
            $userid,
            $cartitem->get_product_sku(),
            $now,
            $cart->get_uuid()
        );
        $context = $eligibility->get_context();
        if (
            !$eligibility->is_eligible()
            || $context === null
            || $context->get_promotion_id() !== (int)($metadata['promotion_join_promotion_id'] ?? 0)
            || $context->get_course_id() !== (int)($metadata['promotion_join_course_id'] ?? 0)
            || $context->get_product_id() !== (int)($metadata['promotion_join_product_id'] ?? 0)
        ) {
            throw new \RuntimeException('Promotion join checkout cohort is no longer valid.');
        }

        $pricing = CommercePedagogicalPromotionJoinPricingService::create($GLOBALS['DB'])->resolve(
            $eligibility,
            $summary->get_currency()
        );
        if (
            !$pricing->is_purchasable()
            || $pricing->get_price_id() === null
            || $pricing->get_amount_minor() === null
            || (int)$pricing->get_amount_minor() !== $linetotalminor
        ) {
            throw new \RuntimeException('Promotion join checkout price is no longer valid.');
        }

        return [
            'promotion_join_price_id' => (int)$pricing->get_price_id(),
            'promotion_join_amount_minor' => (int)$pricing->get_amount_minor(),
            'promotion_join_currency' => $pricing->get_currency(),
        ];
    }

    private function allocate_total(array $items, int $target): array {
        $allocations = array_fill(0, count($items), 0);
        $normalindexes = [];
        $normalbase = 0;
        $reserved = 0;

        foreach ($items as $index => $item) {
            $subtotal = $item->get_subtotal()->get_amount_minor();
            $metadata = $item->get_item()->get_metadata();
            $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
            $isupgrade = $operation === 'upgrade';
            $istrialconversion = $operation === 'trialconversion';
            $ispromotionjoin = $operation === 'promotion_join';

            if ($isupgrade || $istrialconversion || $ispromotionjoin) {
                // Upgrade, Trial and promotion_join values are already locked
                // final prices in the calculated cart snapshot. Never spread
                // unrelated cart-level promotions onto these special lines.
                $allocations[$index] = $subtotal;
                $reserved += $subtotal;
            } else {
                $normalindexes[] = $index;
                $normalbase += $subtotal;
            }
        }

        $normaltarget = max(0, $target - $reserved);
        if ($normalindexes === []) {
            if ($target !== $reserved) {
                throw new \RuntimeException('Locked checkout lines cannot be adjusted by promotions.');
            }
            return $allocations;
        }
        if ($normalbase <= 0) {
            return $allocations;
        }

        $allocated = 0;
        foreach ($normalindexes as $position => $index) {
            $subtotal = $items[$index]->get_subtotal()->get_amount_minor();
            $value = $position === array_key_last($normalindexes)
                ? $normaltarget - $allocated
                : intdiv($normaltarget * $subtotal, $normalbase);
            $allocations[$index] = $value;
            $allocated += $value;
        }
        return $allocations;
    }

    private function map_type(string $type): string {
        return match ($type) {
            'course_access' => CommerceItem::TYPE_SUBSCRIPTION,
            'digital_download' => CommerceItem::TYPE_DIGITAL,
            'bundle' => CommerceItem::TYPE_BUNDLE,
            'service' => CommerceItem::TYPE_SERVICE,
            default => throw new \RuntimeException('Unsupported checkout product type: ' . $type),
        };
    }
}
