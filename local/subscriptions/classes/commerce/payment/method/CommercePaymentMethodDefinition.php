<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\method;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentMethodDefinition {
    public const FAMILY_CARD = 'card';
    public const FAMILY_WALLET = 'wallet';
    public const FAMILY_ACCOUNT = 'account';
    public const FAMILY_BNPL = 'bnpl';

    public const PRESENTATION_PRIMARY = 'primary';
    public const PRESENTATION_SECONDARY = 'secondary';

    public function __construct(
        private readonly string $key,
        private readonly string $family,
        private readonly bool $wallet,
        private readonly bool $deferred,
        private readonly int $displayorder,
        private readonly string $presentation = self::PRESENTATION_SECONDARY
    ) {
        CommercePaymentMethod::normalise($key);

        if (!in_array($family, [
            self::FAMILY_CARD,
            self::FAMILY_WALLET,
            self::FAMILY_ACCOUNT,
            self::FAMILY_BNPL,
        ], true)) {
            throw new \coding_exception(
                'Unknown Commerce payment method family: ' . $family
            );
        }


        if (!in_array($presentation, [
            self::PRESENTATION_PRIMARY,
            self::PRESENTATION_SECONDARY,
        ], true)) {
            throw new \coding_exception(
                'Unknown Commerce payment presentation: ' . $presentation
            );
        }
    }

    public function get_key(): string {
        return CommercePaymentMethod::normalise($this->key);
    }

    public function get_family(): string {
        return $this->family;
    }

    public function is_wallet(): bool {
        return $this->wallet;
    }

    public function is_deferred(): bool {
        return $this->deferred;
    }

    public function get_display_order(): int {
        return $this->displayorder;
    }

    public function get_presentation(): string {
        return $this->presentation;
    }

    public function is_primary_presentation(): bool {
        return $this->presentation === self::PRESENTATION_PRIMARY;
    }

    public function is_secondary_presentation(): bool {
        return $this->presentation === self::PRESENTATION_SECONDARY;
    }
}
