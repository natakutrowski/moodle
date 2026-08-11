<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Builds public URLs for registered Showrooms. */
final class CommerceShowroomUrl {
    public static function make(
        CommerceShowroomDefinition|string $showroom,
        array $params = [],
        ?string $language = null
    ): \moodle_url {
        $definition = is_string($showroom)
            ? CommerceShowroomRegistry::require($showroom)
            : $showroom;
        return new \moodle_url('/' . $definition->get_slug($language), $params);
    }
}
