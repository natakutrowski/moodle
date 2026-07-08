<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class PaymentFailedScanner {

    public function __construct(
        private readonly AutomationScannerRepository $repository = new AutomationScannerRepository(),
        private readonly AutomationDispatcher $dispatcher = new AutomationDispatcher()
    ) {
    }

    public function run(): int {
        $count = 0;
        $payments = $this->repository->get_unprocessed_failed_payment_requests();

        foreach ($payments as $payment) {
            $this->dispatcher->dispatch_entity(
                AutomationTriggerKeys::PAYMENT_FAILED,
                AutomationEntityTypes::PAYMENT_REQUEST,
                (int)$payment->id,
                (int)$payment->userid,
                [
                    'paymentrequestid' => (int)$payment->id,
                    'planid' => (int)$payment->planid,
                    'price' => (float)$payment->price,
                    'amountminor' => (int)$payment->amount_minor,
                    'currency' => (string)$payment->currency,
                    'status' => (string)$payment->status,
                    'creationdate' => (int)$payment->creation_date,
                    'source' => 'cron_payment_failed_scanner',
                ]
            );

            $count++;
        }

        return $count;
    }
}