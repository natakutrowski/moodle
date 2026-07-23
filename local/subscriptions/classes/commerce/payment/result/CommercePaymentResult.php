<?php

namespace local_subscriptions\commerce\payment\result;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent result of a Commerce payment operation.
 */
final class CommercePaymentResult {

    public function __construct(
        private readonly string $requestreference,
        private readonly string $providerkey,
        private readonly string $status,
        private readonly ?string $providerpaymentid = null,
        private readonly ?CommercePaymentAction $action = null,
        private readonly ?string $errorcode = null,
        private readonly ?string $errormessage = null,
        private readonly array $metadata = [],
        private readonly ?int $createdat = null,
        private readonly ?int $updatedat = null
    ) {
        if (trim($requestreference) === '') {
            throw new \coding_exception(
                'A Commerce payment result request reference cannot be empty.'
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
                'Invalid Commerce payment result provider key.'
            );
        }

        CommercePaymentStatus::normalise(
            $status
        );

        $this->validate_consistency();
    }

    public static function requires_action(
        string $requestreference,
        string $providerkey,
        string $providerpaymentid,
        CommercePaymentAction $action,
        array $metadata = []
    ): self {
        return new self(
            $requestreference,
            $providerkey,
            CommercePaymentStatus::REQUIRES_ACTION,
            $providerpaymentid,
            $action,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    public static function pending(
        string $requestreference,
        string $providerkey,
        ?string $providerpaymentid = null,
        array $metadata = []
    ): self {
        return new self(
            $requestreference,
            $providerkey,
            CommercePaymentStatus::PENDING,
            $providerpaymentid,
            null,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    public static function succeeded(
        string $requestreference,
        string $providerkey,
        ?string $providerpaymentid = null,
        array $metadata = []
    ): self {
        return new self(
            $requestreference,
            $providerkey,
            CommercePaymentStatus::SUCCEEDED,
            $providerpaymentid,
            null,
            null,
            null,
            $metadata,
            time(),
            time()
        );
    }

    public static function failed(
        string $requestreference,
        string $providerkey,
        string $errorcode,
        string $errormessage,
        ?string $providerpaymentid = null,
        array $metadata = []
    ): self {
        return new self(
            $requestreference,
            $providerkey,
            CommercePaymentStatus::FAILED,
            $providerpaymentid,
            null,
            $errorcode,
            $errormessage,
            $metadata,
            time(),
            time()
        );
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

    public function get_status(): string {
        return CommercePaymentStatus::normalise(
            $this->status
        );
    }

    public function get_provider_payment_id(): ?string {
        return $this->normalise_nullable_string(
            $this->providerpaymentid
        );
    }

    public function get_action():
        ?CommercePaymentAction {
        return $this->action;
    }

    public function get_error_code(): ?string {
        return $this->normalise_nullable_string(
            $this->errorcode
        );
    }

    public function get_error_message(): ?string {
        return $this->normalise_nullable_string(
            $this->errormessage
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }

    public function get_updated_at(): ?int {
        return $this->updatedat;
    }

    public function is_successful(): bool {
        return CommercePaymentStatus::is_successful(
            $this->status
        );
    }

    public function is_terminal(): bool {
        return CommercePaymentStatus::is_terminal(
            $this->status
        );
    }

    public function requires_customer_action(): bool {
        return CommercePaymentStatus::requires_customer_action(
            $this->status
        );
    }

    public function has_failed(): bool {
        return $this->get_status()
            === CommercePaymentStatus::FAILED;
    }

    public function to_array(): array {
        return [
            'requestreference' =>
                $this->get_request_reference(),

            'providerkey' =>
                $this->get_provider_key(),

            'status' =>
                $this->get_status(),

            'providerpaymentid' =>
                $this->get_provider_payment_id(),

            'action' =>
                $this->action?->to_array(),

            'errorcode' =>
                $this->get_error_code(),

            'errormessage' =>
                $this->get_error_message(),

            'metadata' =>
                $this->get_metadata(),

            'createdat' =>
                $this->get_created_at(),

            'updatedat' =>
                $this->get_updated_at(),
        ];
    }

    private function validate_consistency(): void {
        $status = $this->get_status();

        if (
            $status === CommercePaymentStatus::REQUIRES_ACTION
            && $this->action === null
        ) {
            throw new CommercePaymentResultException(
                'A requires_action Commerce payment result must contain an action.'
            );
        }

        if (
            $status !== CommercePaymentStatus::REQUIRES_ACTION
            && $this->action !== null
        ) {
            throw new CommercePaymentResultException(
                'Only a requires_action Commerce payment result may contain an action.'
            );
        }

        if (
            $status === CommercePaymentStatus::FAILED
            && (
                $this->get_error_code() === null
                || $this->get_error_message() === null
            )
        ) {
            throw new CommercePaymentResultException(
                'A failed Commerce payment result must contain an error code and message.'
            );
        }

        if (
            $status !== CommercePaymentStatus::FAILED
            && (
                $this->get_error_code() !== null
                || $this->get_error_message() !== null
            )
        ) {
            throw new CommercePaymentResultException(
                'Only a failed Commerce payment result may expose payment errors.'
            );
        }
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}