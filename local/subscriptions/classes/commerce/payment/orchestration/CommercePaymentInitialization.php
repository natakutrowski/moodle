<?php

namespace local_subscriptions\commerce\payment\orchestration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Complete result of a Commerce payment initialization.
 */
final class CommercePaymentInitialization {

    public function __construct(
        private readonly string $requestreference,
        private readonly string $providerkey,
        private readonly CommercePaymentProviderValidationResult
            $validation,
        private readonly ?CommercePaymentResult $paymentresult,
        private readonly bool $simulated,
        private readonly int $durationmilliseconds,
        private readonly array $metadata = []
    ) {
        if (trim($requestreference) === '') {
            throw new \coding_exception(
                'A Commerce payment initialization reference cannot be empty.'
            );
        }

        if (
            trim($providerkey) === ''
            || !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                strtolower(trim($providerkey))
            )
        ) {
            throw new \coding_exception(
                'A Commerce payment initialization requires a valid provider key.'
            );
        }

        if ($durationmilliseconds < 0) {
            throw new \coding_exception(
                'A Commerce payment initialization duration cannot be negative.'
            );
        }

        if (
            !$simulated
            && $paymentresult === null
        ) {
            throw new \coding_exception(
                'A real Commerce payment initialization requires a payment result.'
            );
        }

        if (
            $paymentresult !== null
            && $paymentresult->get_request_reference()
                !== trim($requestreference)
        ) {
            throw new \coding_exception(
                'The Commerce payment initialization result reference does not match the request.'
            );
        }

        if (
            $paymentresult !== null
            && $paymentresult->get_provider_key()
                !== strtolower(trim($providerkey))
        ) {
            throw new \coding_exception(
                'The Commerce payment initialization provider does not match its result.'
            );
        }
    }

    public function get_request_reference(): string {
        return trim(
            $this->requestreference
        );
    }

    public function get_provider_key(): string {
        return strtolower(
            trim($this->providerkey)
        );
    }

    public function get_validation():
        CommercePaymentProviderValidationResult {
        return $this->validation;
    }

    public function get_payment_result():
        ?CommercePaymentResult {
        return $this->paymentresult;
    }

    public function is_simulated(): bool {
        return $this->simulated;
    }

    public function was_executed(): bool {
        return !$this->simulated;
    }

    public function get_duration_milliseconds(): int {
        return $this->durationmilliseconds;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function has_warnings(): bool {
        return $this->validation->has_warnings();
    }

    public function requires_customer_action(): bool {
        return $this->paymentresult
            ?->requires_customer_action()
            ?? false;
    }

    public function to_array(): array {
        return [
            'requestreference' =>
                $this->get_request_reference(),

            'providerkey' =>
                $this->get_provider_key(),

            'validation' =>
                $this->validation->to_array(),

            'paymentresult' =>
                $this->paymentresult?->to_array(),

            'simulated' =>
                $this->is_simulated(),

            'durationmilliseconds' =>
                $this->get_duration_milliseconds(),

            'metadata' =>
                $this->get_metadata(),
        ];
    }
}