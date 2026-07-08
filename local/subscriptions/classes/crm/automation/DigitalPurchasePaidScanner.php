<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class DigitalPurchasePaidScanner {

    public function __construct(
        private readonly AutomationScannerRepository $repository = new AutomationScannerRepository(),
        private readonly AutomationDispatcher $dispatcher = new AutomationDispatcher()
    ) {
    }

    public function run(): int {
        $count = 0;
        $purchases = $this->repository->get_unprocessed_paid_digital_purchases();

        foreach ($purchases as $purchase) {
            $this->dispatcher->dispatch_entity(
                AutomationTriggerKeys::DIGITAL_PURCHASE_PAID,
                AutomationEntityTypes::DIGITAL_PAYMENT_REQUEST,
                (int)$purchase->id,
                (int)$purchase->userid,
                [
                    'digitalpaymentrequestid' => (int)$purchase->id,
                    'productid' => (int)$purchase->productid,
                    'email' => (string)$purchase->email,
                    'price' => (float)$purchase->price,
                    'amountminor' => (int)$purchase->amount_minor,
                    'currency' => (string)$purchase->currency,
                    'status' => (string)$purchase->status,
                    'paymentdate' => (int)($purchase->payment_date ?? 0),
                    'creationdate' => (int)$purchase->creation_date,
                    'source' => 'cron_digital_purchase_paid_scanner',
                ]
            );

            $count++;
        }

        return $count;
    }
}