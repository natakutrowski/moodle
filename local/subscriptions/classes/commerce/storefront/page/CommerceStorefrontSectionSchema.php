<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

/** Controlled, versioned editorial section contract. */
final class CommerceStorefrontSectionSchema {
    public const VERSION = 3;
    public const MAX_SECTIONS = 16;

    public const TYPES = [
        'hero',
        'rich_text',
        'image_text',
        'video',
        'h5p',
        'features',
        'program',
        'instructor',
        'testimonial',
        'testimonials',
        'faq',
        'gallery',
        'cta',
        'components',
        'timeline',
        'comparison',
        'accordion',
        // Legacy alias kept readable.
        'media',
    ];

    public const STYLES = [
        'default',
        'soft',
        'accent',
        'contrast',
        'boxed',
        'full_width',
        'glass',
        'gradient',
        'minimal',
    ];

    public static function normalise(array $section, int $position): ?array {
        $type = strtolower(trim((string)($section['type'] ?? '')));
        if (!in_array($type, self::TYPES, true)) {
            return null;
        }

        $style = strtolower(trim((string)($section['style'] ?? 'default')));
        if (!in_array($style, self::STYLES, true)) {
            $style = 'default';
        }

        $id = trim((string)($section['id'] ?? ''));
        if ($id === '' || preg_match('/^[a-z0-9_-]{1,64}$/', $id) !== 1) {
            $id = 'section-' . ($position + 1);
        }

        $visible = !array_key_exists('visible', $section)
            || filter_var($section['visible'], FILTER_VALIDATE_BOOL);

        $order = isset($section['order'])
            ? max(0, min(9999, (int)$section['order']))
            : $position * 10;

        $section['id'] = $id;
        $section['type'] = $type;
        $section['style'] = $style;
        $section['visible'] = $visible;
        $section['order'] = $order;
        $section['layout'] = CommerceStorefrontComposerLayout::normalise($section, $position);

        return $section;
    }

    /** @param array<int,array<string,mixed>> $sections */
    public static function sort_visible(array $sections): array {
        $normalised = [];

        foreach (array_slice($sections, 0, self::MAX_SECTIONS) as $position => $section) {
            if (!is_array($section)) {
                continue;
            }
            $section = self::normalise($section, $position);
            if ($section === null || !$section['visible']) {
                continue;
            }
            $normalised[] = $section;
        }

        usort(
            $normalised,
            static fn(array $left, array $right): int =>
                ((int)$left['order'] <=> (int)$right['order'])
                ?: strcmp((string)$left['id'], (string)$right['id'])
        );

        return array_values($normalised);
    }
}
