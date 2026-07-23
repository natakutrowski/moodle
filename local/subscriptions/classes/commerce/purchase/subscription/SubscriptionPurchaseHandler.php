<?php

namespace local_subscriptions\commerce\purchase\subscription;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\purchase\handler\CommercePreparedPurchaseItem;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandler;
use local_subscriptions\commerce\purchase\handler\CommercePurchasePreparationException;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseValidationResult;

/**
 * Business handler for subscription plan purchases.
 *
 * No subscription or enrolment is created here.
 */
final class SubscriptionPurchaseHandler
    implements CommercePurchaseHandler {

    public const KEY = 'subscription';

    public const FULFILLMENT_KEY =
        'subscription_enrolment';

    public function __construct(
        private readonly ?SubscriptionPlanRepository $planrepository = null
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function supports(
        CommercePurchaseRequestItem $item
    ): bool {
        return $item->get_item()->get_type()
            === CommerceItem::TYPE_SUBSCRIPTION;
    }

    public function validate(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePurchaseValidationResult {
        $result =
            CommercePurchaseValidationResult::valid();

        if (!$this->supports($item)) {
            return $result->add_error(
                'unsupported_subscription_item',
                'The SubscriptionPurchaseHandler does not support this Commerce item.',
                [
                    'itemreference' =>
                        $item->get_item()->get_reference(),
                    'itemtype' =>
                        $item->get_item()->get_type(),
                ]
            );
        }

        if ($item->get_quantity() !== 1) {
            $result->add_error(
                'subscription_quantity_not_supported',
                'A subscription plan can only be purchased once per request item.',
                [
                    'quantity' => $item->get_quantity(),
                ]
            );
        }

        $planid = $this->resolve_plan_id(
            $item
        );

        if ($planid === null) {
            return $result->add_error(
                'subscription_plan_missing',
                'The Commerce subscription item has no valid legacy plan identifier.',
                [
                    'itemreference' =>
                        $item->get_item()->get_reference(),
                ]
            );
        }

        $plan = $this->get_plan_repository()
            ->find($planid);

        if ($plan === null) {
            return $result->add_error(
                'subscription_plan_not_found',
                'The requested subscription plan does not exist.',
                [
                    'planid' => $planid,
                ]
            );
        }

        if (!$plan->is_active()) {
            $result->add_error(
                'subscription_plan_inactive',
                'The requested subscription plan is inactive.',
                [
                    'planid' => $planid,
                ]
            );
        }

        if (
            $plan->is_trial()
            && !$item->is_free()
        ) {
            $result->add_warning(
                'paid_trial_subscription',
                'A trial subscription plan has a non-zero purchase amount.',
                [
                    'planid' => $planid,
                    'amountminor' =>
                        $item->get_total_amount_minor(),
                ]
            );
        }

        if (
            $plan->is_recurring()
            && $customer->is_guest()
        ) {
            $result->add_warning(
                'recurring_subscription_guest_customer',
                'A recurring subscription is being prepared for a guest customer.',
                [
                    'planid' => $planid,
                    'email' => $customer->get_email(),
                ]
            );
        }

        return $result;
    }

    public function prepare(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePreparedPurchaseItem {
        $validation = $this->validate(
            $item,
            $customer
        );

        if (!$validation->is_valid()) {
            throw new CommercePurchasePreparationException(
                'The subscription purchase item is invalid.',
                $validation
            );
        }

        $planid = $this->resolve_plan_id(
            $item
        );

        if ($planid === null) {
            throw new CommercePurchasePreparationException(
                'The subscription plan identifier could not be resolved.',
                $validation
            );
        }

        $plan = $this->get_plan_repository()
            ->find($planid);

        if ($plan === null) {
            throw new CommercePurchasePreparationException(
                'The subscription plan could not be loaded.',
                $validation
            );
        }

        return new CommercePreparedPurchaseItem(
            $item,
            self::KEY,
            self::FULFILLMENT_KEY,
            [
                'item_reference' =>
                    $item->get_item()->get_reference(),

                'item_type' =>
                    CommerceItem::TYPE_SUBSCRIPTION,

                'description' =>
                    $plan->get_name(),

                'customer_email' =>
                    $customer->get_email(),
            ],
            [
                'planid' =>
                    $plan->get_id(),

                'access_scope_id' =>
                    $plan->get_access_scope_id(),

                'duration_key' =>
                    $plan->get_duration_key(),

                'is_trial' =>
                    $plan->is_trial(),

                'is_recurring' =>
                    $plan->is_recurring(),

                'userid' =>
                    $customer->get_user_id(),

                'customer_email' =>
                    $customer->get_email(),
            ],
            [
                'handler' => self::KEY,
                'plan_name' => $plan->get_name(),
                'validation_warnings' =>
                    array_map(
                        static fn($issue): array =>
                            $issue->to_array(),
                        $validation->get_warnings()
                    ),
            ]
        );
    }

    private function resolve_plan_id(
        CommercePurchaseRequestItem $item
    ): ?int {
        $legacyid = $item->get_item()
            ->get_legacy_id();

        if (
            $legacyid !== null
            && $legacyid > 0
        ) {
            return $legacyid;
        }

        $metadataid = (int)$item
            ->get_metadata_value(
                'planid',
                0
            );

        return $metadataid > 0
            ? $metadataid
            : null;
    }

    private function get_plan_repository():
        SubscriptionPlanRepository {
        return $this->planrepository
            ?? new LegacySubscriptionPlanRepository();
    }
}