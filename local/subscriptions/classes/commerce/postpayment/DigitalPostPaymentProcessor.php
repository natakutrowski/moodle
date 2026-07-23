<?php

namespace local_subscriptions\commerce\postpayment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\legacy\DigitalPurchaseFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\Provider;

/**
 * Commerce post-payment processor for Digital Stripe/EUR and Alfa/RUB.
 *
 * When Commerce fulfillment is disabled, the processor explicitly delegates
 * to the Legacy digital payment service. Once Commerce processing starts,
 * failures are fail-closed and Legacy is not executed a second time.
 */
final class DigitalPostPaymentProcessor {

    public function __construct(
        private readonly ?CommercePostPaymentLogger $logger = null
    ) {
    }

    public function process(
        InternalEvent $event
    ): CommercePostPaymentProcessingResult {
        if (!$this->supports($event)) {
            return CommercePostPaymentProcessingResult::unsupported();
        }

        $paymentrequest = $this->find_payment_request($event);

        if ($paymentrequest === null) {
            $this->get_logger()->log('process', 'payment_request_missing', $this->log_context($event));
            return CommercePostPaymentProcessingResult::legacy_required();
        }

        if (!$this->commerce_enabled_for($event, $paymentrequest)) {
            return CommercePostPaymentProcessingResult::legacy_required((int)$paymentrequest->id);
        }

        if ($this->is_fulfilled($paymentrequest)) {
            $this->get_logger()->log('process', 'already_processed', $this->log_context($event, $paymentrequest));
            return CommercePostPaymentProcessingResult::already_processed((int)$paymentrequest->id);
        }

        $runtime = CommerceRuntimeFactory::create();
        $preparation = $runtime->purchase_preparation()->prepare(
            $this->build_purchase_request(
                $event,
                $paymentrequest
            )
        );

        $context = CommerceFulfillmentContext::confirmed(
            'digital-payment-request:' . (int)$paymentrequest->id,
            $this->resolve_provider($event, $paymentrequest),
            $this->resolve_transaction_id($event, $paymentrequest),
            $this->resolve_amount_minor($event, $paymentrequest),
            $this->resolve_currency($event, $paymentrequest),
            time(),
            (int)$paymentrequest->id,
            $this->resolve_provider($event, $paymentrequest) . '_webhook',
            [
                'event_type' => $event->type,
                'payment_context' => 'digital_product',
            ]
        );

        $result = $runtime->post_payment_bridge()->execute($preparation, $context);

        if (!$result->is_enabled()) {
            throw new \RuntimeException(
                'Commerce fulfillment is eligible but the fulfillment bridge is disabled.'
            );
        }

        if (!$result->is_successful()) {
            throw new \RuntimeException(
                'Commerce digital fulfillment failed. Legacy fallback was intentionally not executed.'
            );
        }

        $this->get_logger()->log('process', 'commerce_completed', array_merge(
            $this->log_context($event, $paymentrequest),
            ['commerce_status' => 'fulfilled']
        ));

        return CommercePostPaymentProcessingResult::commerce_completed((int)$paymentrequest->id);
    }

    private function supports(InternalEvent $event): bool {
        if ($event->type !== 'checkout_completed'
            || strtolower((string)($event->meta['payment_context'] ?? '')) !== 'digital_product') {
            return false;
        }

        $provider = strtolower((string)($event->meta['provider'] ?? Provider::STRIPE));
        $currency = strtoupper((string)($event->currency ?? ($provider === Provider::ALFA ? 'RUB' : 'EUR')));

        return ($provider === Provider::STRIPE && $currency === 'EUR')
            || ($provider === Provider::ALFA && $currency === 'RUB');
    }

    private function commerce_enabled_for(
        InternalEvent $event,
        \stdClass $paymentrequest
    ): bool {
        if (empty(get_config(
            'local_subscriptions',
            'commerce_fulfillment_enabled'
        ))) {
            return false;
        }

        $provider = $this->resolve_provider(
            $event,
            $paymentrequest
        );

        $currency = $this->resolve_currency(
            $event,
            $paymentrequest
        );

        return ($provider === Provider::STRIPE && $currency === 'EUR')
            || ($provider === Provider::ALFA && $currency === 'RUB');
    }

    private function find_payment_request(InternalEvent $event): ?\stdClass {
        global $DB;

        if (!empty($event->payment_request_id)) {
            $record = $DB->get_record(
                product_manager::TABLE_PAYMENT_REQUEST,
                ['id' => (int)$event->payment_request_id],
                '*',
                IGNORE_MISSING
            );
            if ($record) {
                return $record;
            }
        }

        $sessionid = trim((string)($event->meta['session'] ?? ''));
        if ($sessionid === '') {
            return null;
        }

        $record = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['sessionid' => $sessionid],
            '*',
            IGNORE_MISSING
        );

        if (!$record) {
            $record = $DB->get_record(
                product_manager::TABLE_PAYMENT_REQUEST,
                ['transactionid' => $sessionid],
                '*',
                IGNORE_MISSING
            );
        }

        return $record ?: null;
    }

    private function build_purchase_request(
        InternalEvent $event,
        \stdClass $paymentrequest
    ): CommercePurchaseRequest {
        $product = product_manager::get_product_by_id((int)$paymentrequest->productid, false);

        if (!$product) {
            throw new \RuntimeException('The digital product required for Commerce fulfillment was not found.');
        }

        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:' . (int)$product->id,
            (string)($product->name ?? ('Digital product #' . (int)$product->id)),
            (int)$product->id,
            ['productid' => (int)$product->id]
        );

        return new CommercePurchaseRequest(
            'digital-payment-request:' . (int)$paymentrequest->id,
            new CommerceCustomer(
                !empty($paymentrequest->userid) ? (int)$paymentrequest->userid : null,
                (string)$paymentrequest->email,
                $paymentrequest->firstname ?? null,
                $paymentrequest->lastname ?? null,
                ['paymentrequestid' => (int)$paymentrequest->id]
            ),
            [
                new CommercePurchaseRequestItem(
                    $item,
                    1,
                    $this->resolve_amount_minor($event, $paymentrequest),
                    $this->resolve_currency(
                        $event,
                        $paymentrequest
                    ),
                    ['productid' => (int)$product->id]
                ),
            ],
            preferredprovider: $this->resolve_provider(
                $event,
                $paymentrequest
            ),
            metadata: [
                'legacy_payment_request_id' => (int)$paymentrequest->id,
                'payment_context' => 'digital_product',
            ],
            createdat: (int)($paymentrequest->creation_date ?? time())
        );
    }

    private function is_fulfilled(\stdClass $paymentrequest): bool {
        return in_array((string)($paymentrequest->status ?? ''), [Status::PAID, Status::COMPLETED], true)
            && !empty($paymentrequest->download_token);
    }


    private function resolve_provider(InternalEvent $event, \stdClass $paymentrequest): string {
        $provider = strtolower(trim((string)($event->meta['provider'] ?? $paymentrequest->payment_provider ?? Provider::STRIPE)));
        return $provider === Provider::ALFA ? Provider::ALFA : Provider::STRIPE;
    }

    private function resolve_currency(InternalEvent $event, \stdClass $paymentrequest): string {
        $currency = strtoupper(trim((string)($event->currency ?? $paymentrequest->currency ?? '')));
        if ($currency !== '') {
            return $currency;
        }
        return $this->resolve_provider($event, $paymentrequest) === Provider::ALFA ? 'RUB' : 'EUR';
    }

    private function resolve_transaction_id(InternalEvent $event, \stdClass $paymentrequest): string {
        foreach ([
            $event->meta['session'] ?? null,
            $event->meta['payment_intent'] ?? null,
            $paymentrequest->transactionid ?? null,
            $paymentrequest->sessionid ?? null,
        ] as $value) {
            if ($value !== null && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        throw new \RuntimeException(
            'The payment confirmation has no transaction identifier.'
        );
    }

    private function resolve_amount_minor(?InternalEvent $event, \stdClass $paymentrequest): int {
        if ($event !== null && $event->amount_minor !== null && $event->amount_minor >= 0) {
            return $event->amount_minor;
        }

        if (isset($paymentrequest->amount_minor) && (int)$paymentrequest->amount_minor >= 0) {
            return (int)$paymentrequest->amount_minor;
        }

        return max(0, (int)round(((float)($paymentrequest->locked_final_price ?? 0)) * 100));
    }

    private function log_context(InternalEvent $event, ?\stdClass $paymentrequest = null): array {
        $requestid = $paymentrequest !== null
            ? (int)$paymentrequest->id
            : (!empty($event->payment_request_id) ? (int)$event->payment_request_id : null);

        return [
            'provider' => $paymentrequest !== null ? $this->resolve_provider($event, $paymentrequest) : strtolower((string)($event->meta['provider'] ?? Provider::STRIPE)),
            'event_type' => $event->type,
            'payment_request_id' => $requestid,
            'currency' => $paymentrequest !== null
                ? $this->resolve_currency($event, $paymentrequest)
                : strtoupper((string)($event->currency ?? '')),
            'legacy_status' => $paymentrequest->status ?? null,
            'correlation_id' => substr(hash('sha256', implode('|', [
                strtolower((string)($event->meta['provider'] ?? Provider::STRIPE)),
                $event->type,
                (string)($requestid ?? 0),
                (string)($event->meta['session'] ?? ''),
            ])), 0, 16),
        ];
    }

    private function get_logger(): CommercePostPaymentLogger {
        return $this->logger ?? new CommercePostPaymentLogger();
    }
}
