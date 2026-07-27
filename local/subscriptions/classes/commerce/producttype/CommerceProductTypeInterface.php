<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract implemented by all Native Commerce product types.
 */
interface CommerceProductTypeInterface {

    public function get_code(): string;

    public function get_label(): string;

    public function get_icon(): string;

    public function get_capabilities(): CommerceProductTypeCapabilities;
}
