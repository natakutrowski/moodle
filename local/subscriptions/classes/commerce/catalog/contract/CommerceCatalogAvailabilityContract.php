<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable availability contract shared by Native and Legacy catalogue sources. */
interface CommerceCatalogAvailabilityContract {
    public function get_availability(): string;
    public function get_available_from(): ?int;
    public function get_available_until(): ?int;
    public function is_available_at(int $timestamp): bool;
}
