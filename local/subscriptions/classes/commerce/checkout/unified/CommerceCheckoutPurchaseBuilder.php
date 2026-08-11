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

        foreach ($calculateditems as $index => $calculated) {
            $cartitem = $calculated->get_item();
            $quantity = $cartitem->get_quantity();
            $linetotal = $allocations[$index];
            $unitamount = intdiv($linetotal, $quantity);
            $remainder = $linetotal - ($unitamount * $quantity);
            // Quantity splitting is deferred to H3. Current catalogue policies use quantity 1 for sellable products.
            if ($remainder !== 0) {
                throw new \RuntimeException('Checkout allocation requires an indivisible per-unit amount.');
            }
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
                array_merge($cartitem->get_metadata(), $pricing->to_metadata(), [
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
                ])
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

            if ($isupgrade || $istrialconversion) {
                // Upgrade and Trial values are already locked final prices in
                // the calculated cart snapshot. Never apply Trial twice here.
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
