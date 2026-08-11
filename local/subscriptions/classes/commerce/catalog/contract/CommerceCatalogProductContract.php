<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable read contract for a unified Commerce catalogue product. */
interface CommerceCatalogProductContract {
    public function get_id(): ?int;
    public function get_sku(): string;
    public function get_name(): string;
    public function get_description(): string;
    public function get_type(): string;
    public function get_editorial_status(): string;
    public function get_visibility(): string;
    public function get_availability(): string;
    public function get_technical_state(): string;
    public function get_origin(): string;
    public function get_available_from(): ?int;
    public function get_available_until(): ?int;
    public function get_metadata(): array;
}
