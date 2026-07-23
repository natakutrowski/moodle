<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

/**
 * Business handler for one family of Commerce items.
 *
 * The handler validates and prepares an item but does not:
 *
 * - contact a payment provider;
 * - write a payment transaction;
 * - create the final purchase;
 * - perform fulfillment.
 */
interface CommercePurchaseHandler {

    /**
     * Stable technical identifier of the handler.
     *
     * Examples:
     *
     * subscription
     * digital
     * bundle
     */
    public function get_key(): string;

    /**
     * Whether this handler supports the requested Commerce item.
     */
    public function supports(
        CommercePurchaseRequestItem $item
    ): bool;

    /**
     * Applies business validation before payment preparation.
     */
    public function validate(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePurchaseValidationResult;

    /**
     * Creates the provider-independent prepared representation.
     *
     * Implementations must reject an invalid item.
     *
     * @throws CommercePurchasePreparationException
     */
    public function prepare(
        CommercePurchaseRequestItem $item,
        CommerceCustomer $customer
    ): CommercePreparedPurchaseItem;
}