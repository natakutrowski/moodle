<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Records provider launch identifiers and redirect details on a Native payment attempt.
 */
final class CommerceCheckoutPaymentLaunchRecorder {
    public function __construct(
        private readonly CommercePaymentRepository $payments
    ) {}

    public function record(
        CommercePaymentAttempt $attempt,
        CommercePaymentInitialization $initialization
    ): CommercePaymentAttempt {
        $paymentid = $attempt->get_id();

        if ($paymentid === null) {
            throw new \RuntimeException('A persisted Commerce payment attempt is required before provider launch recording.');
        }

        $result = $initialization->get_payment_result();

        if (!$result instanceof CommercePaymentResult) {
            throw new \RuntimeException('A real Commerce provider launch must expose a payment result.');
        }

        if ($result->get_provider_key() !== $attempt->get_provider()) {
            throw new \RuntimeException('The Commerce provider launch does not match the persisted payment attempt.');
        }

        $action = $result->get_action();

        if (!$action instanceof CommercePaymentAction || !$result->requires_customer_action()) {
            throw new \RuntimeException('A hosted Commerce provider launch must require a customer action.');
        }

        $paymenturl = $action->get_url();

        if ($paymenturl === null) {
            throw new \RuntimeException('A hosted Commerce provider launch must contain a payment URL.');
        }

        $providerreference = $result->get_provider_payment_id();

        if ($providerreference === null) {
            throw new \RuntimeException('A Commerce provider launch must contain a provider reference.');
        }

        return $this->payments->record_provider_launch(
            $paymentid,
            $providerreference,
            $this->resolve_provider_order_id($result),
            $paymenturl,
            $this->build_provider_payload($result, $action)
        );
    }

    private function resolve_provider_order_id(
        CommercePaymentResult $result
    ): ?string {
        if ($result->get_provider_key() !== 'alfa') {
            return null;
        }

        $metadataorderid = $result->get_metadata_value('alfa_order_id');

        if (is_scalar($metadataorderid) && trim((string) $metadataorderid) !== '') {
            return trim((string) $metadataorderid);
        }

        return $result->get_provider_payment_id();
    }

    private function build_provider_payload(
        CommercePaymentResult $result,
        CommercePaymentAction $action
    ): array {
        return [
            'request_reference' => $result->get_request_reference(),
            'provider' => $result->get_provider_key(),
            'status' => $result->get_status(),
            'provider_payment_id' => $result->get_provider_payment_id(),
            'result_metadata' => $result->get_metadata(),
            'action' => [
                'type' => $action->get_type(),
                'metadata' => $action->get_metadata(),
            ],
            'recorded_at' => time(),
        ];
    }
}
