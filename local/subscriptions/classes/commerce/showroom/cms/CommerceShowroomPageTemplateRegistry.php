<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Reusable starter templates for Showroom pages. */
final class CommerceShowroomPageTemplateRegistry {
    /** @return array<string,array<string,mixed>> */
    public static function definitions(): array {
        return [
            'a1_course' => [
                'label' => 'Cours A1 — voyage CampusFR',
                'blocks' => [
                    'hero', 'stats', 'anchor_nav', 'audience_profiles',
                    'adventure_overview', 'content_highlights', 'community_clan',
                    'tutor_chat', 'video', 'curriculum_tabs', 'journey_map',
                    'preparation_flow', 'offers', 'testimonials', 'faq', 'final_cta',
                ],
            ],
            'launch' => [
                'label' => 'Landing de lancement',
                'blocks' => ['hero', 'stats', 'problem', 'problem_interactive', 'learning_method', 'video', 'content_highlights', 'ascent', 'stage_method', 'exercise_explorer', 'offers', 'comparison', 'memory_method', 'trust', 'bonus', 'faq', 'support', 'final_cta'],
            ],
            'digital' => [
                'label' => 'Produit digital',
                'blocks' => ['hero', 'stats', 'video', 'content_highlights', 'offers', 'bonus', 'faq', 'support', 'final_cta'],
            ],
            'course' => [
                'label' => 'Formation',
                'blocks' => ['hero', 'stats', 'problem', 'problem_interactive', 'learning_method', 'ascent', 'stage_method', 'exercise_explorer', 'offers', 'memory_method', 'trust', 'faq', 'support', 'final_cta'],
            ],
            'bundle' => [
                'label' => 'Bundle',
                'blocks' => ['hero', 'stats', 'offers', 'comparison', 'memory_method', 'trust', 'bonus', 'faq', 'support', 'final_cta'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function get(string $key): array {
        $definitions = self::definitions();
        if (!isset($definitions[$key])) {
            throw new \invalid_parameter_exception('Unknown showroom page template.');
        }
        return $definitions[$key];
    }
}
