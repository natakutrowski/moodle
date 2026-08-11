<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable read contract for content promised by a catalogue product. */
interface CommerceCatalogFulfillmentContract {
    public function get_type(): string;
    public function get_resource_key(): string;
    public function get_duration_seconds(): ?int;
    public function get_quantity(): int;
    public function get_configuration(): array;
    public function get_origin(): string;
}
