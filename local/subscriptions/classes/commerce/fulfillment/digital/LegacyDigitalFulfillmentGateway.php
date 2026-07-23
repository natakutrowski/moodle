<?php

namespace local_subscriptions\commerce\fulfillment\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\constants\Status;
use local_subscriptions\digital\product_manager;

/**
 * Grants digital download access through the current payment request table.
 */
final class LegacyDigitalFulfillmentGateway
    implements DigitalFulfillmentGateway {

    public function find_payment_request(
        int $paymentrequestid
    ): ?\stdClass {
        global $DB;

        if ($paymentrequestid <= 0) {
            return null;
        }

        $record = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $paymentrequestid],
            '*',
            IGNORE_MISSING
        );

        return $record ?: null;
    }

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): \stdClass {
        global $DB;

        $paymentrequestid = $context->get_payment_request_id();

        if ($paymentrequestid === null) {
            throw new \coding_exception(
                'Digital fulfillment requires a payment request identifier.'
            );
        }

        $record = $this->find_payment_request($paymentrequestid);

        if ($record === null) {
            throw new \coding_exception(
                'The digital payment request could not be found.'
            );
        }

        $productid = (int)$operation->get_metadata_value('productid', 0);

        if ($productid <= 0 || (int)$record->productid !== $productid) {
            throw new \coding_exception(
                'The digital fulfillment product does not match the payment request.'
            );
        }

        $update = (object)[
            'id' => (int)$record->id,
            'status' => Status::PAID,
            'payment_date' => $context->get_paid_at(),
            'last_update' => time(),
            'payment_provider' => $context->get_provider(),
            'transactionid' => $context->get_transaction_id(),
        ];

        if (empty($record->download_token)) {
            $update->download_token =
                product_manager::generate_download_token();
            $update->download_token_expires =
                $context->get_metadata_value(
                    'download_token_expires'
                );
        }

        $DB->update_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            $update
        );

        return $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => (int)$record->id],
            '*',
            MUST_EXIST
        );
    }
}
