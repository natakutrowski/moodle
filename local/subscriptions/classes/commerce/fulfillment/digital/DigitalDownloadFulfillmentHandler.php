<?php

namespace local_subscriptions\commerce\fulfillment\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;
use local_subscriptions\constants\Status;

/**
 * Fulfills digital purchases by granting download access.
 */
final class DigitalDownloadFulfillmentHandler
    implements CommerceFulfillmentHandler {

    public const KEY = 'digital_download';

    public function __construct(
        private readonly ?DigitalFulfillmentGateway $gateway = null
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function supports(
        CommerceFulfillmentOperation $operation
    ): bool {
        return $operation->get_key() === self::KEY;
    }

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): CommerceFulfillmentResult {
        $paymentrequestid = $context->get_payment_request_id();

        if ($paymentrequestid === null) {
            throw new \coding_exception(
                'Digital fulfillment requires a payment request identifier.'
            );
        }

        $gateway = $this->gateway
            ?? new LegacyDigitalFulfillmentGateway();

        $existing = $gateway->find_payment_request(
            $paymentrequestid
        );

        if (
            $existing !== null
            && in_array(
                $existing->status ?? '',
                [Status::PAID, Status::COMPLETED],
                true
            )
            && !empty($existing->download_token)
        ) {
            return new CommerceFulfillmentResult(
                $operation,
                CommerceFulfillmentResult::STATUS_SKIPPED,
                'The digital payment was already fulfilled.',
                [
                    'paymentrequestid' => (int)$existing->id,
                    'download_token' => $existing->download_token,
                    'idempotency_key' =>
                        $operation->get_idempotency_key(),
                ]
            );
        }

        $record = $gateway->fulfill(
            $operation,
            $context
        );

        return new CommerceFulfillmentResult(
            $operation,
            CommerceFulfillmentResult::STATUS_COMPLETED,
            'Digital download access was granted.',
            [
                'paymentrequestid' => (int)$record->id,
                'productid' => (int)$record->productid,
                'download_token' => $record->download_token ?? null,
                'download_token_expires' =>
                    $record->download_token_expires ?? null,
                'idempotency_key' =>
                    $operation->get_idempotency_key(),
            ]
        );
    }
}
