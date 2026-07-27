<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared immutable implementation for simple Commerce product type descriptors.
 */
abstract class AbstractCommerceProductType implements CommerceProductTypeInterface {

    public function __construct(
        private readonly string $code,
        private readonly string $label,
        private readonly string $icon,
        private readonly CommerceProductTypeCapabilities $capabilities
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $code)) {
            throw new \coding_exception('A Commerce product type code must be a valid machine identifier.');
        }

        if (trim($label) === '') {
            throw new \coding_exception('A Commerce product type label cannot be empty.');
        }
    }

    public function get_code(): string {
        return $this->code;
    }

    public function get_label(): string {
        return $this->label;
    }

    public function get_icon(): string {
        return $this->icon;
    }

    public function get_capabilities(): CommerceProductTypeCapabilities {
        return $this->capabilities;
    }
}
