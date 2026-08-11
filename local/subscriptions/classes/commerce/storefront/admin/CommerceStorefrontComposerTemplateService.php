<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\page\CommerceStorefrontComposerLayout;

/** Provides controlled starter structures for the visual page composer. */
final class CommerceStorefrontComposerTemplateService {
    public const TEMPLATES = ['sales', 'course', 'digital', 'bundle'];

    /** @return array<int,array<string,mixed>> */
    public function sections(string $template, int $startposition = 0): array {
        $template = strtolower(trim($template));
        if (!in_array($template, self::TEMPLATES, true)) {
            return [];
        }

        $definitions = match ($template) {
            'sales' => [
                ['hero', 'accent', 1, '100'],
                ['features', 'soft', 1, '100'],
                ['testimonial', 'default', 1, '100'],
                ['faq', 'soft', 1, '100'],
                ['cta', 'accent', 1, '100'],
            ],
            'course' => [
                ['hero', 'accent', 1, '100'],
                ['rich_text', 'default', 1, '100'],
                ['program', 'soft', 1, '100'],
                ['instructor', 'default', 1, '100'],
                ['testimonials', 'soft', 1, '100'],
                ['faq', 'default', 1, '100'],
                ['cta', 'accent', 1, '100'],
            ],
            'digital' => [
                ['hero', 'accent', 1, '100'],
                ['features', 'soft', 1, '100'],
                ['image_text', 'default', 1, '100'],
                ['image_text', 'soft', 1, '100'],
                ['gallery', 'default', 1, '100'],
                ['faq', 'soft', 1, '100'],
                ['cta', 'accent', 1, '100'],
            ],
            'bundle' => [
                ['hero', 'accent', 1, '100'],
                ['rich_text', 'default', 1, '100'],
                ['features', 'soft', 1, '100'],
                ['program', 'default', 1, '100'],
                ['testimonials', 'soft', 1, '100'],
                ['cta', 'accent', 1, '100'],
            ],
        };

        $sections = [];
        foreach ($definitions as $offset => [$type, $background, $columns, $ratio]) {
            $position = $startposition + $offset;
            $sections[] = [
                'id' => 'section-' . ($position + 1),
                'type' => $type,
                'visible' => true,
                'order' => $position * 10,
                'style' => 'default',
                'layout' => CommerceStorefrontComposerLayout::normalise([
                    'layout' => [
                        'rowid' => 'row-' . ($position + 1),
                        'column' => 1,
                        'columns' => $columns,
                        'ratio' => $ratio,
                        'width' => 'contained',
                        'background' => $background,
                        'spacing' => 'medium',
                        'alignment' => 'stretch',
                    ],
                ], $position),
            ];
        }

        return $sections;
    }
}
