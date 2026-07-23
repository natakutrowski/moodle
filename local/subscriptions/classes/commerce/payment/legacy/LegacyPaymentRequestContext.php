<?php

namespace local_subscriptions\commerce\payment\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\Provider;

/**
 * Immutable description of a persisted Legacy payment request.
 *
 * Transitional object used while Commerce delegates payment initialization
 * to the existing payment infrastructure.
 */
final class LegacyPaymentRequestContext {

    public const TABLE_SUBSCRIPTION =
        'subscription_payment_request';

    public const TABLE_DIGITAL =
        product_manager::TABLE_PAYMENT_REQUEST;

    public const CONTEXT_SUBSCRIPTION =
        'subscription';

    public const CONTEXT_DIGITAL_PRODUCT =
        'digital_product';

    private const ALLOWED_TABLES = [
        self::TABLE_SUBSCRIPTION,
        self::TABLE_DIGITAL,
    ];

    private const ALLOWED_CONTEXTS = [
        self::CONTEXT_SUBSCRIPTION,
        self::CONTEXT_DIGITAL_PRODUCT,
    ];

    private const ALLOWED_PROVIDERS = [
        Provider::STRIPE,
        Provider::ALFA,
    ];

    public function __construct(
        private readonly int $paymentrequestid,
        private readonly string $paymentrequesttable,
        private readonly string $paymentcontext,
        private readonly string $provider,
        private readonly string $ordernumberprefix,
        private readonly ?string $language = null,
        private readonly ?string $mode = null,
        private readonly ?string $stripepriceid = null,
        private readonly array $metadata = []
    ) {
        if ($paymentrequestid <= 0) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment request identifier must be positive.',
                'legacy_payment_request_id_invalid',
                [
                    'paymentrequestid' =>
                        $paymentrequestid,
                ]
            );
        }

        $table = strtolower(
            trim($paymentrequesttable)
        );

        if (
            !in_array(
                $table,
                self::ALLOWED_TABLES,
                true
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The requested Legacy payment table is not allowed.',
                'legacy_payment_request_table_not_allowed',
                [
                    'table' =>
                        $paymentrequesttable,
                ]
            );
        }

        $context = strtolower(
            trim($paymentcontext)
        );

        if (
            !in_array(
                $context,
                self::ALLOWED_CONTEXTS,
                true
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment context is not supported.',
                'legacy_payment_context_invalid',
                [
                    'paymentcontext' =>
                        $paymentcontext,
                ]
            );
        }

        $provider = strtolower(
            trim($provider)
        );

        if (
            !in_array(
                $provider,
                self::ALLOWED_PROVIDERS,
                true
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment provider is not supported.',
                'legacy_payment_provider_invalid',
                [
                    'provider' =>
                        $provider,
                ]
            );
        }

        if (trim($ordernumberprefix) === '') {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy order number prefix cannot be empty.',
                'legacy_order_number_prefix_missing'
            );
        }

        $this->validate_table_context_pair(
            $table,
            $context
        );
    }

    public static function from_metadata(
        array $metadata,
        string $expectedprovider
    ): self {
        $paymentrequestid =
            self::required_positive_int(
                $metadata,
                'legacy_payment_request_id'
            );

        $paymentrequesttable =
            self::required_string(
                $metadata,
                'legacy_payment_request_table'
            );

        $paymentcontext =
            self::required_string(
                $metadata,
                'legacy_payment_context'
            );

        $ordernumberprefix =
            self::optional_string(
                $metadata,
                'legacy_order_number_prefix'
            );

        if ($ordernumberprefix === null) {
            $ordernumberprefix =
                self::default_order_number_prefix(
                    $paymentrequesttable
                );
        }

        return new self(
            $paymentrequestid,
            $paymentrequesttable,
            $paymentcontext,
            $expectedprovider,
            $ordernumberprefix,
            self::optional_string(
                $metadata,
                'legacy_language'
            ),
            self::optional_string(
                $metadata,
                'legacy_mode'
            ),
            self::optional_string(
                $metadata,
                'legacy_stripe_price_id'
            ),
            $metadata
        );
    }

    public function get_payment_request_id(): int {
        return $this->paymentrequestid;
    }

    public function get_payment_request_table(): string {
        return strtolower(
            trim($this->paymentrequesttable)
        );
    }

    public function get_payment_context(): string {
        return strtolower(
            trim($this->paymentcontext)
        );
    }

    public function get_provider(): string {
        return strtolower(
            trim($this->provider)
        );
    }

    public function get_order_number_prefix(): string {
        return trim(
            $this->ordernumberprefix
        );
    }

    public function get_language(): ?string {
        return $this->normalise_nullable_string(
            $this->language
        );
    }

    public function get_mode(): string {
        $mode = $this->normalise_nullable_string(
            $this->mode
        );

        return $mode ?? 'payment';
    }

    public function get_stripe_price_id(): ?string {
        return $this->normalise_nullable_string(
            $this->stripepriceid
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_subscription(): bool {
        return $this->get_payment_request_table()
            === self::TABLE_SUBSCRIPTION;
    }

    public function is_digital(): bool {
        return $this->get_payment_request_table()
            === self::TABLE_DIGITAL;
    }

    public function get_order_number(): string {
        return sprintf(
            '%s-%d',
            $this->get_order_number_prefix(),
            $this->get_payment_request_id()
        );
    }

    private function validate_table_context_pair(
        string $table,
        string $context
    ): void {
        $valid =
            (
                $table === self::TABLE_SUBSCRIPTION
                && $context === self::CONTEXT_SUBSCRIPTION
            )
            ||
            (
                $table === self::TABLE_DIGITAL
                && $context === self::CONTEXT_DIGITAL_PRODUCT
            );

        if (!$valid) {
            throw new LegacyPaymentRequestMappingException(
                'The Legacy payment table does not match its payment context.',
                'legacy_payment_table_context_mismatch',
                [
                    'table' =>
                        $table,

                    'paymentcontext' =>
                        $context,
                ]
            );
        }
    }

    private static function default_order_number_prefix(
        string $table
    ): string {
        return strtolower(trim($table))
            === self::TABLE_DIGITAL
                ? 'digital'
                : 'sub';
    }

    private static function required_positive_int(
        array $metadata,
        string $key
    ): int {
        $value = $metadata[$key] ?? null;

        if (
            !is_int($value)
            && !(
                is_string($value)
                && ctype_digit($value)
            )
        ) {
            throw new LegacyPaymentRequestMappingException(
                sprintf(
                    'Required Legacy metadata "%s" is missing or invalid.',
                    $key
                ),
                'legacy_metadata_invalid',
                [
                    'key' =>
                        $key,
                ]
            );
        }

        $value = (int)$value;

        if ($value <= 0) {
            throw new LegacyPaymentRequestMappingException(
                sprintf(
                    'Required Legacy metadata "%s" must be positive.',
                    $key
                ),
                'legacy_metadata_invalid',
                [
                    'key' =>
                        $key,

                    'value' =>
                        $value,
                ]
            );
        }

        return $value;
    }

    private static function required_string(
        array $metadata,
        string $key
    ): string {
        $value = self::optional_string(
            $metadata,
            $key
        );

        if ($value === null) {
            throw new LegacyPaymentRequestMappingException(
                sprintf(
                    'Required Legacy metadata "%s" is missing.',
                    $key
                ),
                'legacy_metadata_missing',
                [
                    'key' =>
                        $key,
                ]
            );
        }

        return $value;
    }

    private static function optional_string(
        array $metadata,
        string $key
    ): ?string {
        $value = $metadata[$key] ?? null;

        if (
            $value === null
            || !is_scalar($value)
        ) {
            return null;
        }

        $value = trim(
            (string)$value
        );

        return $value !== ''
            ? $value
            : null;
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