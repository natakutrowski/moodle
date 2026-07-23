<?php

namespace local_subscriptions\commerce\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;

/**
 * Builds provider-independent payment requests from purchase preparations.
 */
final class CommercePaymentRequestFactory {

    public function create(
        CommercePurchasePreparation $preparation
    ): CommercePaymentRequest {
        $purchaserequest =
            $preparation->get_request();

        $purchasecustomer =
            $purchaserequest->get_customer();

        $lines = [];

        foreach (
            $preparation->get_payment_lines()
            as $line
        ) {
            $lines[] =
                new CommercePaymentLine(
                    (string)$line['reference'],
                    (string)$line['description'],
                    (int)$line['quantity'],
                    (int)$line['unitamountminor'],
                    (string)$line['currency'],
                    [
                        'handler' =>
                            $line['handler'] ?? null,

                        'totalamountminor' =>
                            $line['totalamountminor'] ?? null,
                    ]
                );
        }

        if ($lines === []) {
            throw new CommercePaymentRequestException(
                'The Commerce purchase preparation contains no payment lines.'
            );
        }

        return new CommercePaymentRequest(
            $preparation->get_reference(),
            new CommercePaymentCustomer(
                $purchasecustomer->get_user_id(),
                $purchasecustomer->get_email(),
                $purchasecustomer->get_first_name(),
                $purchasecustomer->get_last_name(),
                $purchasecustomer->get_metadata()
            ),
            $lines,
            $preparation->get_currency(),
            $preparation->get_total_amount_minor(),
            $purchaserequest->get_preferred_provider(),
            $purchaserequest->get_return_url(),
            $purchaserequest->get_cancel_url(),
            array_merge(
                $purchaserequest->get_metadata(),
                [
                    'purchase_reference' =>
                        $preparation->get_reference(),

                    'prepared_at' =>
                        $preparation->get_prepared_at(),

                    'fulfillment_operations' =>
                        $preparation
                            ->get_fulfillment_operations(),
                ]
            ),
            time()
        );
    }
}