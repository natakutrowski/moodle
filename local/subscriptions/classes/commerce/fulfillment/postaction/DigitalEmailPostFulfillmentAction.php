<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;
use local_subscriptions\digital\product_manager;
use local_subscriptions\mailer;
use local_subscriptions\service\DigitalPurchaseEmailService;

/**
 * Sends digital access and receipt emails without duplicating completed sends.
 */
final class DigitalEmailPostFulfillmentAction
    implements CommercePostFulfillmentAction {

    public function get_key(): string {
        return 'digital_emails';
    }

    public function supports(
        CommerceFulfillmentResult $result
    ): bool {
        return $result->get_operation()->get_key()
            === DigitalDownloadFulfillmentHandler::KEY;
    }

    public function execute(
        CommerceFulfillmentResult $result,
        CommerceFulfillmentContext $context
    ): CommercePostFulfillmentActionResult {
        global $DB;

        $purchaseid = (int)(
            $result->get_metadata()['paymentrequestid'] ?? 0
        );

        if ($purchaseid <= 0) {
            return new CommercePostFulfillmentActionResult(
                $this->get_key(),
                CommercePostFulfillmentActionResult::STATUS_SKIPPED,
                'Digital email context is incomplete.'
            );
        }

        $pr = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        $accesssent = (int)($pr->emailsent ?? 0) === 1;
        $receiptsent = (int)($pr->receipt_sent ?? 0) === 1;

        if ($accesssent && $receiptsent) {
            return new CommercePostFulfillmentActionResult(
                $this->get_key(),
                CommercePostFulfillmentActionResult::STATUS_SKIPPED,
                'Digital emails were already sent.',
                ['paymentrequestid' => $purchaseid]
            );
        }

        if (!$accesssent) {
            DigitalPurchaseEmailService::resend_access_email($purchaseid);
        }

        $pr = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        if ((int)($pr->receipt_sent ?? 0) !== 1) {
            $product = product_manager::get_product_by_id(
                (int)$pr->productid,
                false
            );

            if (!$product) {
                throw new \RuntimeException(
                    'The digital product required for the receipt was not found.'
                );
            }

            $DB->set_field(
                product_manager::TABLE_PAYMENT_REQUEST,
                'receipt_sent',
                2,
                ['id' => $purchaseid]
            );

            try {
                mailer::dispatch(mailer::T_DIGITAL_RECEIPT, [
                    'pr' => $pr,
                    'product' => $product,
                ]);

                $DB->update_record(
                    product_manager::TABLE_PAYMENT_REQUEST,
                    (object)[
                        'id' => $purchaseid,
                        'receipt_sent' => 1,
                        'last_update' => time(),
                    ]
                );
            } catch (\Throwable $exception) {
                $DB->update_record(
                    product_manager::TABLE_PAYMENT_REQUEST,
                    (object)[
                        'id' => $purchaseid,
                        'receipt_sent' => 0,
                        'last_error' => '[digital_receipt_email] ' . $exception->getMessage(),
                        'last_update' => time(),
                    ]
                );
                throw $exception;
            }
        }

        return new CommercePostFulfillmentActionResult(
            $this->get_key(),
            CommercePostFulfillmentActionResult::STATUS_COMPLETED,
            'Digital access and receipt emails were sent.',
            ['paymentrequestid' => $purchaseid]
        );
    }
}
