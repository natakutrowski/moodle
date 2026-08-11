<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentCustomer;
use local_subscriptions\commerce\payment\CommercePaymentLine;
use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;

/** Converts the frozen purchase request into the existing provider-neutral payment contract. */
final class CommerceCheckoutPaymentRequestBuilder {
    public function build(CommercePurchaseRequest $purchase): CommercePaymentRequest {
        $customer = $purchase->get_customer();
        $lines = array_map(static fn($item): CommercePaymentLine => new CommercePaymentLine(
            $item->get_item()->get_reference(),
            $item->get_item()->get_name(),
            $item->get_quantity(),
            $item->get_unit_amount_minor(),
            $item->get_currency(),
            ['itemtype' => $item->get_item()->get_type(), 'purchaseitem' => $item->get_metadata()]
        ), $purchase->get_items());

        return new CommercePaymentRequest(
            $purchase->get_reference(),
            new CommercePaymentCustomer(
                $customer->get_user_id(),
                $customer->get_email(),
                $customer->get_first_name(),
                $customer->get_last_name(),
                $customer->get_metadata()
            ),
            $lines,
            $purchase->get_currency(),
            $purchase->get_total_amount_minor(),
            $purchase->get_preferred_provider(),
            $purchase->get_return_url(),
            $purchase->get_cancel_url(),
            array_merge($purchase->get_metadata(), ['purchase_reference' => $purchase->get_reference()]),
            time()
        );
    }
}
