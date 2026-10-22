<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Canonical catalogue of blocks supported by the Showroom CMS. */
final class CommerceShowroomBlockTypeRegistry {
    /** @return array<string,array{label:string,icon:string,addable?:bool}> */
    public static function definitions(): array {
        return [
            'hero' => ['label' => 'Hero', 'icon' => 'fa-mountain-sun'],
            'stats' => ['label' => 'Chiffres clés du Hero', 'icon' => 'fa-chart-simple'],
            'anchor_nav' => ['label' => 'Navigation interne', 'icon' => 'fa-compass'],
            'adventure_overview' => ['label' => 'Aventure — vue d’ensemble', 'icon' => 'fa-route'],
            'community_clan' => ['label' => 'Communauté / clan', 'icon' => 'fa-people-group'],
            'tutor_chat' => ['label' => 'Tuteur / conversation', 'icon' => 'fa-comments'],
            'curriculum_tabs' => ['label' => 'Programme à onglets', 'icon' => 'fa-table-list'],
            'journey_map' => ['label' => 'Carte / itinéraire', 'icon' => 'fa-map-location-dot'],
            'preparation_flow' => ['label' => 'Parcours préparatoire', 'icon' => 'fa-person-walking-arrow-right'],
            'problem' => ['label' => 'Pourquoi est-ce si difficile ?', 'icon' => 'fa-triangle-exclamation'],
            'problem_interactive' => ['label' => 'Hésitation interactive', 'icon' => 'fa-hand-pointer'],
            'learning_method' => ['label' => 'Apprendre comme on apprend à conduire', 'icon' => 'fa-car-side'],
            'video' => ['label' => 'Vidéo', 'icon' => 'fa-circle-play'],
            'content_highlights' => ['label' => 'Un vrai entraînement', 'icon' => 'fa-list-check'],
            'audience_profiles' => ['label' => 'Audience — 4 profils illustrés', 'icon' => 'fa-users'],
            'heart_puzzle' => ['label' => 'Cœur puzzle', 'icon' => 'fa-heart'],
            'before_after' => ['label' => 'Avant / Après', 'icon' => 'fa-arrow-right-arrow-left'],
            'ascent' => ['label' => 'Ascension en 30 étapes', 'icon' => 'fa-mountain'],
            'stage_method' => ['label' => 'Déroulement d’une étape', 'icon' => 'fa-route'],
            'exercise_explorer' => ['label' => 'Explorateur d’exercices', 'icon' => 'fa-puzzle-piece'],
            'offers' => ['label' => 'Offres', 'icon' => 'fa-tags'],
            'comparison' => ['label' => 'Comparatif', 'icon' => 'fa-table-columns'],
            'memory_method' => ['label' => 'Méthode conçue pour la mémoire', 'icon' => 'fa-brain'],
            'trust' => ['label' => 'Réassurance', 'icon' => 'fa-shield-halved'],
            'testimonials' => ['label' => 'Témoignages', 'icon' => 'fa-comments'],
            'bonus' => ['label' => 'Bonus', 'icon' => 'fa-layer-group'],
            'faq' => ['label' => 'FAQ', 'icon' => 'fa-circle-question'],
            'support' => ['label' => 'Support', 'icon' => 'fa-headset'],
            'faq_contact' => ['label' => 'Support sous FAQ (A1)', 'icon' => 'fa-comments'],
            'verbs_cards' => ['label' => 'Focus cartes de verbes', 'icon' => 'fa-address-card'],
            'final_cta' => ['label' => 'Le sommet vous attend', 'icon' => 'fa-flag-checkered'],
            'html' => ['label' => 'Bloc éditorial libre', 'icon' => 'fa-code'],

            // Legacy aliases kept only so pre-J16G.2 records remain readable/migratable.
            'journey' => ['label' => 'Parcours / timeline (ancien)', 'icon' => 'fa-route', 'addable' => false],
            'method' => ['label' => 'Méthode (ancien)', 'icon' => 'fa-lightbulb', 'addable' => false],
            'cta' => ['label' => 'Appel à l’action (ancien)', 'icon' => 'fa-arrow-pointer', 'addable' => false],
        ];
    }

    /** @return array<string,mixed> */
    public static function editor_schema(string $type): array {
        return CommerceShowroomBlockEditorRegistry::schema($type);
    }

    public static function exists(string $type): bool {
        return array_key_exists($type, self::definitions());
    }
}
