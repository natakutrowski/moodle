<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\execution;

defined('MOODLE_INTERNAL') || die();

final class CommerceCheckoutPaymentRoute {
    public const SURFACE_EXPRESS = 'express';
    public const SURFACE_INLINE = 'inline';
    public const SURFACE_HOSTED = 'hosted';

    public function __construct(
        private readonly string $method,
        private readonly string $provider,
        private readonly string $surface
    ) {}

    public function get_method(): string {
        return $this->method;
    }

    public function get_provider(): string {
        return $this->provider;
    }

    public function get_surface(): string {
        return $this->surface;
    }

    public function is_express(): bool {
        return $this->surface === self::SURFACE_EXPRESS;
    }

    public function is_inline(): bool {
        return $this->surface === self::SURFACE_INLINE;
    }

    public function is_hosted(): bool {
        return $this->surface === self::SURFACE_HOSTED;
    }
}
