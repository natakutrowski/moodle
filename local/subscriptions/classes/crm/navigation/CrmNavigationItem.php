<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

/**
 * Immutable CRM application navigation item.
 */
final class CrmNavigationItem {

    /**
     * @param string $key Stable navigation identifier.
     * @param string $label User-facing translated label.
     * @param string $icon Decorative icon.
     * @param moodle_url $url Destination URL.
     * @param string $capability Capability required to display the item.
     * @param int $position Display order.
     * @param array<int,array{label:string,url:moodle_url,capability:string,icon:string}> $children Contextual shortcuts.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon,
        public readonly moodle_url $url,
        public readonly string $capability,
        public readonly int $position,
        public readonly array $children = []
    ) {
        if (trim($this->key) === '') {
            throw new \coding_exception(
                'CRM navigation item key cannot be empty.'
            );
        }

        if (trim($this->label) === '') {
            throw new \coding_exception(
                'CRM navigation item label cannot be empty.'
            );
        }

        if (trim($this->capability) === '') {
            throw new \coding_exception(
                'CRM navigation item capability cannot be empty.'
            );
        }
    }
}