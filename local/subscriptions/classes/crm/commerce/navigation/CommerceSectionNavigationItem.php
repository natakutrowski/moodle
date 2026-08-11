<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\navigation;

defined('MOODLE_INTERNAL') || die();

use moodle_url;

/** Immutable item used by the Commerce secondary navigation. */
final class CommerceSectionNavigationItem {
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon,
        public readonly moodle_url $url,
        public readonly string $capability,
        public readonly int $position
    ) {
        if (trim($this->key) === '') {
            throw new \coding_exception('Commerce navigation item key cannot be empty.');
        }

        if (trim($this->label) === '') {
            throw new \coding_exception('Commerce navigation item label cannot be empty.');
        }

        if (trim($this->capability) === '') {
            throw new \coding_exception('Commerce navigation item capability cannot be empty.');
        }
    }
}
