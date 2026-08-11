<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\UrlFactory;

/** Maps a Showroom definition and Commerce offers to Mustache data. */
final class CommerceShowroomPresenter {
    /** @param array<int,array<string,mixed>> $offers @return array<string,mixed> */
    public function present(
        CommerceShowroomDefinition $definition,
        array $offers,
        string $currency
    ): array {
        $ownedroles = [];
        foreach ($offers as $candidate) {
            if (!empty($candidate['owned'])) {
                $ownedroles[] = (string)($candidate['role'] ?? '');
            }
        }
        $preferredrole = in_array('pdf', $ownedroles, true) && !in_array('course', $ownedroles, true)
            ? 'course'
            : (in_array('course', $ownedroles, true) && !in_array('pdf', $ownedroles, true) ? 'pdf' : 'bundle');

        foreach ($offers as &$offer) {
            $role = (string)($offer['role'] ?? '');
            $offer['cannotbuy'] = empty($offer['canbuy']);
            $offer['ownedlabel'] = get_string('commerce_storefront_owned', 'local_subscriptions');
            $offer['ownedcompactlabel'] = get_string('commerce_showroom_owned_compact', 'local_subscriptions');
            $offer['ispreferred'] = $role === $preferredrole && empty($offer['owned']);
            $offer['isfeatured'] = $role === 'bundle' && empty($offer['bundleblocked']);
            $offer['featuredlabel'] = get_string('commerce_showroom_offer_featured', 'local_subscriptions');
            $offer['features'] = $this->offer_features($role);
            $offer['hasfeatures'] = $offer['features'] !== [];
        }
        unset($offer);

        $hero = $this->hero_action($offers);

        return [
            'showroomkey' => $definition->get_key(),
            'title' => get_string($definition->get_title_key(), 'local_subscriptions'),
            'description' => get_string($definition->get_description_key(), 'local_subscriptions'),
            'eyebrow' => get_string('commerce_showroom_eyebrow', 'local_subscriptions'),
            'herocta' => $hero['label'],
            'heroactionurl' => $hero['url'],
            'heroactionisanchor' => $hero['anchor'],
            'herostatus' => $hero['status'],
            'heroexpeditionlabel' => get_string('commerce_showroom_hero_expedition', 'local_subscriptions'),
            'herostagelabel' => get_string('commerce_showroom_hero_stage', 'local_subscriptions'),
            'herostat1value' => '180',
            'herostat1label' => get_string('commerce_showroom_hero_stat_verbs', 'local_subscriptions'),
            'herostat2value' => '30',
            'herostat2label' => get_string('commerce_showroom_hero_stat_stages', 'local_subscriptions'),
            'herostat3value' => '4000+',
            'herostat3label' => get_string('commerce_showroom_hero_stat_exercises', 'local_subscriptions'),
            'herostat4value' => get_string('commerce_showroom_hero_stat_lifetime_value', 'local_subscriptions'),
            'herostat4label' => get_string('commerce_showroom_hero_stat_lifetime', 'local_subscriptions'),
            'hero_summary' => get_string('commerce_showroom_hero_summary', 'local_subscriptions'),
            'videomodalclose' => get_string('commerce_showroom_video_close', 'local_subscriptions'),
            'herosecondarycta' => get_string('commerce_showroom_hero_secondary_cta', 'local_subscriptions'),
            'heroproof' => get_string('commerce_showroom_hero_proof', 'local_subscriptions'),
            'problemeyebrow' => get_string('commerce_showroom_problem_eyebrow', 'local_subscriptions'),
            'problemtitle' => get_string('commerce_showroom_problem_title', 'local_subscriptions'),
            'problemdescription' => get_string('commerce_showroom_problem_description', 'local_subscriptions'),
            'problems' => $this->problem_cards(),
            'methodtitle' => get_string('commerce_showroom_method_title', 'local_subscriptions'),
            'methoddescription' => get_string('commerce_showroom_method_description', 'local_subscriptions'),
            'methodsteps' => $this->method_steps(),
            'videotitle' => get_string('commerce_showroom_video_title', 'local_subscriptions'),
            'videodescription' => get_string('commerce_showroom_video_description', 'local_subscriptions'),
            'videoplaceholder' => get_string('commerce_showroom_video_placeholder', 'local_subscriptions'),
            'contenteyebrow' => get_string('commerce_showroom_content_eyebrow', 'local_subscriptions'),
            'contenttitle' => get_string('commerce_showroom_content_title', 'local_subscriptions'),
            'contentdescription' => get_string('commerce_showroom_content_description', 'local_subscriptions'),
            'contentstats' => $this->content_stats(),
            'ascenteyebrow' => get_string('commerce_showroom_ascent_eyebrow', 'local_subscriptions'),
            'ascenttitle' => get_string('commerce_showroom_ascent_title', 'local_subscriptions'),
            'ascentdescription' => get_string('commerce_showroom_ascent_description', 'local_subscriptions'),
            'ascentarialabel' => get_string('commerce_showroom_ascent_aria', 'local_subscriptions'),
            'ascentcheckpoints' => $this->ascent_checkpoints(),
            'ascentlegend1' => get_string('commerce_showroom_ascent_legend_1', 'local_subscriptions'),
            'ascentlegend2' => get_string('commerce_showroom_ascent_legend_2', 'local_subscriptions'),
            'ascentlegend3' => get_string('commerce_showroom_ascent_legend_3', 'local_subscriptions'),
            'journeytitle' => get_string('commerce_showroom_journey_title', 'local_subscriptions'),
            'journeydescription' => get_string('commerce_showroom_journey_description', 'local_subscriptions'),
            'journeysteps' => $this->journey_steps(),
            'exerciseeyebrow' => get_string('commerce_showroom_exercises_eyebrow', 'local_subscriptions'),
            'exercisetitle' => get_string('commerce_showroom_exercises_title', 'local_subscriptions'),
            'exercisedescription' => get_string('commerce_showroom_exercises_description', 'local_subscriptions'),
            'exercisearialabel' => get_string('commerce_showroom_exercises_aria', 'local_subscriptions'),
            'exercisepreviewlabel' => get_string('commerce_showroom_exercises_preview_label', 'local_subscriptions'),
            'exercisepreviewstep' => get_string('commerce_showroom_exercises_preview_step', 'local_subscriptions'),
            'exercisepreviewcaption' => get_string('commerce_showroom_exercises_preview_caption', 'local_subscriptions'),
            'exerciseinitialtitle' => get_string('commerce_showroom_exercise_1_title', 'local_subscriptions'),
            'exerciseinitialtext' => get_string('commerce_showroom_exercise_1_text', 'local_subscriptions'),
            'exerciseinitialicon' => 'fa-solid fa-arrow-pointer',
            'exercises' => $this->exercise_cards(),
            'offersheading' => get_string('commerce_showroom_offers_heading', 'local_subscriptions'),
            'offersdescription' => get_string('commerce_showroom_offers_description', 'local_subscriptions'),
            'offers' => $offers,
            'hasoffers' => $offers !== [],
            'comparisoneyebrow' => get_string('commerce_showroom_comparison_eyebrow', 'local_subscriptions'),
            'comparisontitle' => get_string('commerce_showroom_comparison_title', 'local_subscriptions'),
            'comparisondescription' => get_string('commerce_showroom_comparison_description', 'local_subscriptions'),
            'comparisonfeaturelabel' => get_string('commerce_showroom_comparison_feature', 'local_subscriptions'),
            'comparisonoffers' => $this->comparison_offers($offers),
            'comparisonrows' => $this->comparison_rows(),
            'comparisonincludedlabel' => get_string('commerce_showroom_comparison_included', 'local_subscriptions'),
            'comparisonnotincludedlabel' => get_string('commerce_showroom_comparison_not_included', 'local_subscriptions'),
            'comparisonbundlelabel' => get_string('commerce_showroom_comparison_bundle_badge', 'local_subscriptions'),
            'whyeyebrow' => get_string('commerce_showroom_why_eyebrow', 'local_subscriptions'),
            'whytitle' => get_string('commerce_showroom_why_title', 'local_subscriptions'),
            'whydescription' => get_string('commerce_showroom_why_description', 'local_subscriptions'),
            'whyitems' => $this->why_items(),
            'trustitems' => $this->trust_items(),
            'testimonialseyebrow' => get_string('commerce_showroom_testimonials_eyebrow', 'local_subscriptions'),
            'testimonialstitle' => get_string('commerce_showroom_testimonials_title', 'local_subscriptions'),
            'testimonials' => [],
            'hastestimonials' => false,
            'bonusheading' => get_string('commerce_showroom_bonus_heading', 'local_subscriptions'),
            'bonustext' => get_string('commerce_showroom_bonus_text', 'local_subscriptions'),
            'bonuscta' => get_string('commerce_showroom_bonus_cta', 'local_subscriptions'),
            'faqeyebrow' => get_string('commerce_showroom_faq_eyebrow', 'local_subscriptions'),
            'faqheading' => get_string('commerce_showroom_faq_heading', 'local_subscriptions'),
            'faqdescription' => get_string('commerce_showroom_faq_description', 'local_subscriptions'),
            'faqs' => $this->faqs(),
            'supporttitle' => get_string('commerce_showroom_support_title', 'local_subscriptions'),
            'supporttext' => get_string('commerce_showroom_support_text', 'local_subscriptions'),
            'supportcta' => get_string('commerce_showroom_support_cta', 'local_subscriptions'),
            'supporturl' => UrlFactory::support([
                'source' => 'showroom',
                'showroom' => $definition->get_key(),
            ])->out(false),
            'supportemail' => (string)(
                get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr'
            ),
            'supportemailurl' => 'mailto:' . rawurlencode(
                (string)(get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr')
            ),
            'supportimageurl' => (new \moodle_url(
                '/local/subscriptions/pix/support/gustave_support.png'
            ))->out(false),
            'expeditionlabel' => get_string('commerce_showroom_expedition_card_label', 'local_subscriptions'),
            'expeditionstage' => get_string('commerce_showroom_expedition_card_stage', 'local_subscriptions'),
            'expeditionaltitude' => get_string('commerce_showroom_expedition_card_altitude', 'local_subscriptions'),
            'finaleyebrow' => get_string('commerce_showroom_final_eyebrow', 'local_subscriptions'),
            'finaltitle' => get_string('commerce_showroom_final_title', 'local_subscriptions'),
            'finaltext' => get_string('commerce_showroom_final_text', 'local_subscriptions'),
            'finalcta' => $hero['label'],
            'finalactionurl' => $hero['url'],
            'desktopstickylabel' => get_string('commerce_showroom_desktop_sticky_label', 'local_subscriptions'),
            'desktopstickycta' => $hero['label'],
            'desktopstickyurl' => $hero['url'],
            'desktopstickyprice' => $this->featured_price($offers),
            'hasdesktopstickyprice' => $this->featured_price($offers) !== '',
            'currency' => $currency,
            'currencylabel' => get_string('currency_selector_label', 'local_subscriptions'),
            'showroomurl' => CommerceShowroomUrl::make($definition, ['currency' => $currency])->out(false),
            'shopurl' => UrlFactory::digital_catalog(['currency' => $currency])->out(false),
            'shoplabel' => get_string('commerce_showroom_back_to_shop', 'local_subscriptions'),
            'carturl' => UrlFactory::cart(['currency' => $currency])->out(false),
            'cartlabel' => get_string('commerce_cart_title', 'local_subscriptions'),
        ];
    }


    /**
     * Selects a contextual Hero action without duplicating Commerce ownership rules.
     *
     * @param array<int,array<string,mixed>> $offers
     * @return array{label:string,url:string,anchor:bool,status:string}
     */
    private function hero_action(array $offers): array {
        $byrole = [];
        foreach ($offers as $offer) {
            $role = (string)($offer['role'] ?? '');
            if ($role !== '') {
                $byrole[$role] = $offer;
            }
        }

        $bundle = $byrole['bundle'] ?? [];
        if (!empty($bundle['owned']) && !empty($bundle['ownedactionurl'])) {
            return [
                'label' => get_string('commerce_showroom_hero_cta_resume', 'local_subscriptions'),
                'url' => (string)$bundle['ownedactionurl'],
                'anchor' => false,
                'status' => 'owned-bundle',
            ];
        }

        $course = $byrole['course'] ?? [];
        $pdf = $byrole['pdf'] ?? [];
        if (!empty($pdf['owned']) && empty($course['owned'])) {
            return [
                'label' => get_string('commerce_showroom_hero_cta_complete_course', 'local_subscriptions'),
                'url' => '#showroom-offers',
                'anchor' => true,
                'status' => 'owned-pdf',
            ];
        }
        if (!empty($course['owned']) && empty($pdf['owned'])) {
            return [
                'label' => get_string('commerce_showroom_hero_cta_complete_pdf', 'local_subscriptions'),
                'url' => '#showroom-offers',
                'anchor' => true,
                'status' => 'owned-course',
            ];
        }

        return [
            'label' => get_string('commerce_showroom_hero_cta_start', 'local_subscriptions'),
            'url' => '#showroom-offers',
            'anchor' => true,
            'status' => 'prospect',
        ];
    }

    /** @return array<int,array<string,string>> */
    private function problem_cards(): array {
        return $this->numbered_items('commerce_showroom_problem_', 4, [
            'fa-solid fa-shuffle',
            'fa-solid fa-brain',
            'fa-solid fa-table-cells-large',
            'fa-solid fa-hourglass-half',
        ]);
    }

    /** @return array<int,array<string,string>> */
    private function method_steps(): array {
        return $this->numbered_items('commerce_showroom_method_', 3, [
            'fa-solid fa-eye',
            'fa-solid fa-route',
            'fa-solid fa-arrows-rotate',
        ]);
    }

    /** @return array<int,array<string,string>> */
    private function content_stats(): array {
        return $this->numbered_items('commerce_showroom_stat_', 6, [
            'fa-solid fa-person-hiking',
            'fa-solid fa-list-check',
            'fa-solid fa-headphones',
            'fa-solid fa-rotate',
            'fa-solid fa-award',
            'fa-solid fa-mountain-sun',
        ]);
    }

    /** @return array<int,array<string,string|bool>> */
    private function ascent_checkpoints(): array {
        $altitudes = ['1 035 m', '1 900 m', '2 650 m', '3 400 m', '4 808 m'];
        $stages = ['1–6', '7–12', '13–18', '19–24', '25–30'];
        $icons = [
            'fa-solid fa-house-chimney',
            'fa-solid fa-tree',
            'fa-solid fa-mountain',
            'fa-solid fa-snowflake',
            'fa-solid fa-flag-checkered',
        ];
        $items = [];
        for ($index = 1; $index <= 5; $index++) {
            $items[] = [
                'altitude' => $altitudes[$index - 1],
                'stages' => get_string('commerce_showroom_ascent_stages', 'local_subscriptions', $stages[$index - 1]),
                'icon' => $icons[$index - 1],
                'title' => get_string('commerce_showroom_ascent_' . $index . '_title', 'local_subscriptions'),
                'text' => get_string('commerce_showroom_ascent_' . $index . '_text', 'local_subscriptions'),
                'summit' => $index === 5,
            ];
        }
        return $items;
    }

    /** @return array<int,array<string,string>> */
    private function journey_steps(): array {
        return $this->numbered_items('commerce_showroom_journey_', 6, [
            'fa-solid fa-language',
            'fa-solid fa-microphone-lines',
            'fa-solid fa-puzzle-piece',
            'fa-solid fa-pen-to-square',
            'fa-solid fa-trophy',
            'fa-solid fa-flag-checkered',
        ]);
    }

    /** @return array<int,array<string,string|bool>> */
    private function exercise_cards(): array {
        $items = $this->numbered_items('commerce_showroom_exercise_', 10, [
            'fa-solid fa-arrow-pointer',
            'fa-solid fa-list-ul',
            'fa-solid fa-circle-check',
            'fa-solid fa-highlighter',
            'fa-solid fa-puzzle-piece',
            'fa-solid fa-keyboard',
            'fa-solid fa-volume-high',
            'fa-solid fa-pen',
            'fa-solid fa-bolt',
            'fa-solid fa-medal',
        ]);
        foreach ($items as $index => &$item) {
            $item['active'] = $index === 0;
        }
        unset($item);
        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $offers
     * @return array<int,array<string,mixed>>
     */
    private function comparison_offers(array $offers): array {
        $order = ['pdf' => 1, 'bundle' => 2, 'course' => 3];
        $result = [];
        foreach ($offers as $offer) {
            $role = (string)($offer['role'] ?? '');
            if (!isset($order[$role])) {
                continue;
            }
            $result[] = [
                'role' => $role,
                'name' => (string)($offer['name'] ?? ''),
                'priceformatted' => (string)($offer['priceformatted'] ?? ''),
                'available' => !empty($offer['available']),
                'featured' => $role === 'bundle',
                'sortorder' => $order[$role],
            ];
        }
        usort($result, static fn(array $left, array $right): int =>
            ((int)$left['sortorder']) <=> ((int)$right['sortorder'])
        );
        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    private function comparison_rows(): array {
        $definitions = [
            ['interactive_course', false, true, true],
            ['downloadable_pdf', true, false, true],
            ['verbs_180', true, true, true],
            ['exercises_4000', false, true, true],
            ['audio_video', false, true, true],
            ['offline_revision', true, false, true],
            ['lifetime_access', true, true, true],
        ];
        $rows = [];
        foreach ($definitions as [$key, $pdf, $course, $bundle]) {
            $rows[] = [
                'label' => get_string('commerce_showroom_comparison_' . $key, 'local_subscriptions'),
                'pdf' => $this->comparison_cell($pdf),
                'bundle' => $this->comparison_cell($bundle, true),
                'course' => $this->comparison_cell($course),
            ];
        }
        return $rows;
    }

    /** @return array{included:bool,notincluded:bool,featured:bool} */
    private function comparison_cell(bool $included, bool $featured = false): array {
        return [
            'included' => $included,
            'notincluded' => !$included,
            'featured' => $featured,
        ];
    }

    /** @return array<int,array<string,string>> */
    private function why_items(): array {
        return $this->numbered_items('commerce_showroom_why_', 5, [
            'fa-solid fa-repeat',
            'fa-solid fa-comments',
            'fa-solid fa-headphones',
            'fa-solid fa-gamepad',
            'fa-solid fa-brain',
        ]);
    }

    /** @return array<int,array<string,string>> */
    private function trust_items(): array {
        return $this->numbered_items('commerce_showroom_trust_', 4, [
            'fa-solid fa-shield-halved',
            'fa-solid fa-bolt',
            'fa-solid fa-infinity',
            'fa-solid fa-headset',
        ]);
    }

    /** @param array<int,array<string,mixed>> $offers */
    private function featured_price(array $offers): string {
        foreach ($offers as $offer) {
            if ((string)($offer['role'] ?? '') === 'bundle' && !empty($offer['available'])) {
                return (string)($offer['priceformatted'] ?? '');
            }
        }
        return '';
    }

    /** @return array<int,array<string,string>> */
    private function faqs(): array {
        $items = [];
        for ($index = 1; $index <= 7; $index++) {
            $items[] = [
                'id' => 'showroom-faq-' . $index,
                'question' => get_string('commerce_showroom_faq_' . $index . '_q', 'local_subscriptions'),
                'answer' => get_string('commerce_showroom_faq_' . $index . '_a', 'local_subscriptions'),
            ];
        }
        return $items;
    }

    /** @return array<int,array<string,string>> */
    private function offer_features(string $role): array {
        if (!in_array($role, ['course', 'pdf', 'bundle'], true)) {
            return [];
        }

        $items = [];
        for ($index = 1; $index <= 4; $index++) {
            $items[] = [
                'text' => get_string(
                    'commerce_showroom_offer_' . $role . '_feature_' . $index,
                    'local_subscriptions'
                ),
            ];
        }
        return $items;
    }

    /**
     * @param string[] $icons
     * @return array<int,array<string,string>>
     */
    private function numbered_items(string $prefix, int $count, array $icons): array {
        $items = [];
        for ($index = 1; $index <= $count; $index++) {
            $items[] = [
                'number' => str_pad((string)$index, 2, '0', STR_PAD_LEFT),
                'icon' => $icons[$index - 1] ?? 'fa-solid fa-star',
                'title' => get_string($prefix . $index . '_title', 'local_subscriptions'),
                'text' => get_string($prefix . $index . '_text', 'local_subscriptions'),
            ];
        }
        return $items;
    }
}
