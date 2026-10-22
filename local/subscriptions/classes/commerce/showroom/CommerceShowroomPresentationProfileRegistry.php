<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Resolves the presentation profile associated with a supported Showroom render template. */
final class CommerceShowroomPresentationProfileRegistry {
    public static function for_definition(
        CommerceShowroomDefinition $definition
    ): CommerceShowroomPresentationProfile {
        return self::for_template($definition->get_template());
    }

    public static function for_template(string $template): CommerceShowroomPresentationProfile {
        return match (trim($template)) {
            'local_subscriptions/showroom/default' =>
                new CommerceShowroomDefaultPresentationProfile(),
            'local_subscriptions/showroom/third_group_verbs' =>
                new CommerceShowroomThirdGroupVerbsPresentationProfile(),
            default => throw new \invalid_parameter_exception(
                'Unsupported Showroom presentation profile.'
            ),
        };
    }
}
