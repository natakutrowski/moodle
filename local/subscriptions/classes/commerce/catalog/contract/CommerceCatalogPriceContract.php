<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable read contract for a catalogue price. */
interface CommerceCatalogPriceContract {
    public function get_currency(): string;
    public function get_amount_minor(): int;
    public function get_provider(): ?string;
    public function is_active(): bool;
    public function get_origin(): string;
}
