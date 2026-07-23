<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * Common contract implemented by Stripe, Alfa and future payment providers.
 */
interface CommercePaymentProvider {

    /**
     * Stable technical key.
     *
     * Examples:
     *
     * stripe
     * alfa
     * paypal
     */
    public function get_key(): string;

    /**
     * Provider selection priority.
     *
     * A higher value wins when several providers support the same request.
     */
    public function get_priority(): int;

    /**
     * Whether the provider is correctly configured and usable.
     */
    public function is_available(): bool;

    /**
     * Declares provider capabilities.
     */
    public function get_capabilities():
        CommercePaymentProviderCapabilities;

    /**
     * Fast compatibility check used by the registry.
     *
     * This method must not contact the remote provider.
     */
    public function supports(
        CommercePaymentRequest $request
    ): bool;

    /**
     * Detailed provider-specific validation.
     *
     * This method must not create a remote payment.
     */
    public function validate(
        CommercePaymentRequest $request
    ): CommercePaymentProviderValidationResult;

    /**
     * Initializes a new payment with the provider.
     *
     * Implementations must use the idempotency key from the context.
     *
     * @throws CommercePaymentProviderException
     */
    public function initialize(
        CommercePaymentRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult;

    /**
     * Retrieves the current provider status of an existing payment.
     *
     * @throws CommercePaymentProviderException
     */
    public function retrieve(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult;

    /**
     * Cancels an existing provider payment when supported.
     *
     * @throws CommercePaymentProviderException
     */
    public function cancel(
        string $providerpaymentid,
        CommercePaymentProviderContext $context
    ): CommercePaymentResult;
}