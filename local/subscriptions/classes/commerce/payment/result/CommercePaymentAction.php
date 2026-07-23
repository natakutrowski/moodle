<?php

namespace local_subscriptions\commerce\payment\result;

defined('MOODLE_INTERNAL') || die();

/**
 * Action the customer must perform to continue a Commerce payment.
 */
final class CommercePaymentAction {

    public const TYPE_REDIRECT = 'redirect';

    public const TYPE_FORM_POST = 'form_post';

    public const TYPE_NONE = 'none';

    private const VALID_TYPES = [
        self::TYPE_REDIRECT,
        self::TYPE_FORM_POST,
        self::TYPE_NONE,
    ];

    public function __construct(
        private readonly string $type,
        private readonly ?string $url = null,
        private readonly array $parameters = [],
        private readonly array $metadata = []
    ) {
        $type = strtolower(
            trim($type)
        );

        if (
            !in_array(
                $type,
                self::VALID_TYPES,
                true
            )
        ) {
            throw new \coding_exception(
                'Unsupported Commerce payment action type: '
                . $type
            );
        }

        if (
            in_array(
                $type,
                [
                    self::TYPE_REDIRECT,
                    self::TYPE_FORM_POST,
                ],
                true
            )
            && trim((string)$url) === ''
        ) {
            throw new \coding_exception(
                'A Commerce payment action URL is required.'
            );
        }
    }

    public static function redirect(
        string $url,
        array $metadata = []
    ): self {
        return new self(
            self::TYPE_REDIRECT,
            $url,
            [],
            $metadata
        );
    }

    public static function form_post(
        string $url,
        array $parameters,
        array $metadata = []
    ): self {
        return new self(
            self::TYPE_FORM_POST,
            $url,
            $parameters,
            $metadata
        );
    }

    public static function none(): self {
        return new self(
            self::TYPE_NONE
        );
    }

    public function get_type(): string {
        return strtolower(
            trim($this->type)
        );
    }

    public function get_url(): ?string {
        if ($this->url === null) {
            return null;
        }

        $value = trim($this->url);

        return $value !== ''
            ? $value
            : null;
    }

    public function get_parameters(): array {
        return $this->parameters;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_redirect(): bool {
        return $this->get_type()
            === self::TYPE_REDIRECT;
    }

    public function is_form_post(): bool {
        return $this->get_type()
            === self::TYPE_FORM_POST;
    }

    public function is_none(): bool {
        return $this->get_type()
            === self::TYPE_NONE;
    }

    public function to_array(): array {
        return [
            'type' => $this->get_type(),
            'url' => $this->get_url(),
            'parameters' => $this->get_parameters(),
            'metadata' => $this->get_metadata(),
        ];
    }
}