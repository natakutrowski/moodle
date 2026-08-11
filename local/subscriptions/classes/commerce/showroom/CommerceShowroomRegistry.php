<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Registry of bespoke public Commerce showrooms. */
final class CommerceShowroomRegistry {
    public const THIRD_GROUP_VERBS = 'third-group-verbs';

    // Product SKUs used by the third-group verbs Showroom.
    public const THIRD_GROUP_VERBS_COURSE_SKU = 'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE';
    public const THIRD_GROUP_VERBS_PDF_SKU = 'DIGITAL.VERBES-3E-GROUPE';
    public const THIRD_GROUP_VERBS_BUNDLE_SKU = 'BUNDLE.THIRD_GROUP_VERBS_BUNDLE';

    public static function get(string $key): ?CommerceShowroomDefinition {
        return self::definitions()[$key] ?? null;
    }

    public static function require(string $key): CommerceShowroomDefinition {
        $definition = self::get($key);
        if ($definition === null) {
            throw new \moodle_exception('commerce_showroom_not_found', 'local_subscriptions');
        }
        return $definition;
    }

    public static function find_by_slug(string $slug): ?CommerceShowroomDefinition {
        $slug = trim(strtolower($slug), '/');
        foreach (self::definitions() as $definition) {
            if (in_array($slug, $definition->get_slugs(), true)) {
                return $definition;
            }
        }
        return null;
    }

    /** @return array<string,CommerceShowroomDefinition> */
    public static function definitions(): array {
        static $definitions = null;
        if ($definitions !== null) {
            return $definitions;
        }

        $definition = new CommerceShowroomDefinition(
            self::THIRD_GROUP_VERBS,
            [
                'fr' => 'verbes-3e-groupe',
                'en' => 'third-group-verbs',
                'ru' => 'glagoly-tretey-gruppy',
            ],
            'local_subscriptions/showroom/third_group_verbs',
            [
                'course' => self::THIRD_GROUP_VERBS_COURSE_SKU,
                'pdf' => self::THIRD_GROUP_VERBS_PDF_SKU,
                'bundle' => self::THIRD_GROUP_VERBS_BUNDLE_SKU,
            ],
            'commerce_showroom_third_group_verbs_title',
            'commerce_showroom_third_group_verbs_description'
        );

        $definitions = [$definition->get_key() => $definition];
        return $definitions;
    }
}
