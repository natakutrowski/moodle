<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\contract;

defined('MOODLE_INTERNAL') || die();

/** Localised presentation contract for a unified catalogue product. */
interface CommerceCatalogPresentationContract {
    public function get_language(): string;
    public function get_name(): string;
    public function get_short_description(): string;
    public function get_description(): string;
}
