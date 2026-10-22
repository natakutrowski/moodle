<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\a1;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomAliasConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

/**
 * Creates the hidden CMS foundation for the CampusFR A1 Showroom.
 *
 * The service is deliberately idempotent and never overwrites an existing
 * editorial record. A0 is part of the A1 course itself and therefore has no
 * separate Commerce product role or entitlement here.
 */
final class CommerceA1ShowroomFoundationService {
    public const SHOWROOM_KEY = 'a1-course';
    public const PRODUCT_ROLE_FR = 'course_fr';
    public const PRODUCT_ROLE_RU = 'course_ru';
    public const PRODUCT_SKU_FR = 'A1-FR-SUPPORT';
    public const PRODUCT_SKU_RU = 'A1-RU-SUPPORT';

    /** @var string[] */
    private const STARTER_BLOCKS = [
        'hero',
        'audience_profiles',
        'content_highlights',
        'video',
        'offers',
        'faq',
        'final_cta',
    ];

    /** @var string[] */
    private const E2_BLOCKS = [
        'hero',
        'stats',
        'anchor_nav',
        'audience_profiles',
        'adventure_overview',
        'content_highlights',
        'community_clan',
        'tutor_chat',
        'video',
        'curriculum_tabs',
        'journey_map',
        'preparation_flow',
        'offers',
        'testimonials',
        'faq',
        'final_cta',
    ];

    /** @var string[] Canonical editorial order once E3 is seeded. */
    private const E3_BLOCKS = [
        'hero',
        'stats',
        'anchor_nav',
        'audience_profiles',
        'adventure_overview',
        'content_highlights',
        'community_clan',
        'tutor_chat',
        'support',
        'video',
        'curriculum_tabs',
        'journey_map',
        'preparation_flow',
        'offers',
        'testimonials',
        'faq',
        'final_cta',
    ];

    /** @var string[] */
    private const E3_ENABLED_BLOCKS = [
        'hero',
        'stats',
        'anchor_nav',
        'audience_profiles',
        'adventure_overview',
        'content_highlights',
        'community_clan',
        'tutor_chat',
        'support',
        'video',
    ];

    public function __construct(
        private readonly CommerceShowroomCmsRepository $showrooms
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(new CommerceShowroomCmsRepository($db));
    }

    /**
     * Ensures that the A1 Showroom exists as an unpublished CMS draft.
     *
     * 7.98E.7.2: the A1 commercial catalogue has two explicit Native products,
     * one for French-language support and one for Russian-language support.
     * The Trial conversion SKU is deliberately unrelated to normal Showroom
     * merchandising and must never be used as a product-binding fallback.
     */
    public function ensure(?int $actoruserid = null): int {
        $existing = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($existing !== null) {
            return (int)$existing->id;
        }

        $products = [
            self::PRODUCT_ROLE_FR => self::PRODUCT_SKU_FR,
            self::PRODUCT_ROLE_RU => self::PRODUCT_SKU_RU,
        ];
        $userid = max(0, (int)($actoruserid ?? 0));

        $showroomid = $this->showrooms->save([
            'showroomkey' => self::SHOWROOM_KEY,
            'status' => 'draft',
            'name' => 'Cours A1 CampusFR',
            'template' => 'local_subscriptions/showroom/default',
            // Public slugs stay empty until the final marketing route is chosen.
            'slugfr' => '',
            'slugen' => '',
            'slugru' => '',
            'titlekey' => 'commerce_showroom_a1_title',
            'descriptionkey' => 'commerce_showroom_a1_description',
            'productsjson' => json_encode(
                $products,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
            'settingsjson' => json_encode([
                'offers' => [
                    self::PRODUCT_ROLE_FR => [
                        'detailsenabled' => false,
                        'donationmode' => 'none',
                    ],
                    self::PRODUCT_ROLE_RU => [
                        'detailsenabled' => false,
                        'donationmode' => 'none',
                    ],
                ],
                'foundation' => [
                    'kind' => 'a1_course',
                    'a0included' => true,
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ], $userid);

        foreach (self::STARTER_BLOCKS as $index => $blocktype) {
            $this->showrooms->save_block($showroomid, [
                'blocktype' => $blocktype,
                'blockkey' => $blocktype,
                'sortorder' => ($index + 1) * 10,
                'enabled' => true,
                'configjson' => '{}',
            ], $userid);
        }

        return $showroomid;
    }

    /**
     * 7.98E.2 — extends an existing E1 draft with the reusable A1 block set.
     *
     * Existing editorial JSON and enabled/disabled state are preserved. Missing
     * blocks are inserted disabled so an upgrade can never publish empty
     * sections by surprise. The method only normalises the recommended order.
     */
    public function ensure_builder_structure(?int $actoruserid = null): int {
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null) {
            $this->ensure($actoruserid);
            $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        }
        if ($showroom === null) {
            return 0;
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $existing = [];
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $existing[(string)$block->blocktype] = $block;
        }

        $orderedids = [];
        $created = 0;
        foreach (self::E2_BLOCKS as $index => $blocktype) {
            if (isset($existing[$blocktype])) {
                $block = $existing[$blocktype];
                $blockid = $this->showrooms->save_block($showroomid, [
                    'id' => (int)$block->id,
                    'blocktype' => (string)$block->blocktype,
                    'blockkey' => (string)$block->blockkey,
                    'sortorder' => ($index + 1) * 10,
                    'enabled' => (int)$block->enabled === 1,
                    'configjson' => (string)$block->configjson,
                ], $userid);
            } else {
                $blockid = $this->showrooms->save_block($showroomid, [
                    'blocktype' => $blocktype,
                    'blockkey' => $blocktype,
                    'sortorder' => ($index + 1) * 10,
                    'enabled' => false,
                    'configjson' => '{}',
                ], $userid);
                $created++;
            }
            $orderedids[] = $blockid;
        }

        // Keep any custom/editor-added blocks after the canonical A1 skeleton.
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            if (!in_array((int)$block->id, $orderedids, true)) {
                $orderedids[] = (int)$block->id;
            }
        }
        $this->showrooms->reorder_blocks($showroomid, $orderedids, $userid);
        return $created;
    }

    /**
     * 7.98E.3 — seeds the Russian source copy from the approved A1 showroom PPT.
     *
     * Only empty block configurations are populated. Any block already edited
     * in the Builder is preserved byte-for-byte and its enabled state is left
     * untouched. FR/EN translations deliberately remain empty: E3 uses only
     * the Russian source supplied for the showroom and does not invent copy.
     *
     * @return array{seeded:int,created:int,preserved:int}
     */
    public function apply_editorial_seed_v1(?int $actoruserid = null): array {
        $this->ensure_builder_structure($actoruserid);
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null || (string)$showroom->status !== 'draft') {
            return ['seeded' => 0, 'created' => 0, 'preserved' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $blocks = $this->showrooms->blocks($showroomid);
        $bytype = [];
        foreach ($blocks as $block) {
            $type = (string)$block->blocktype;
            if (!isset($bytype[$type])) {
                $bytype[$type] = $block;
            }
        }

        $created = 0;
        if (!isset($bytype['support'])) {
            $supportid = $this->showrooms->save_block($showroomid, [
                'blocktype' => 'support',
                'blockkey' => 'teacher_support',
                'sortorder' => 90,
                'enabled' => false,
                'configjson' => '{}',
            ], $userid);
            $bytype['support'] = $this->showrooms->get_block($supportid);
            $created++;
        }

        $configs = $this->editorial_seed_v1();
        $seeded = 0;
        $preserved = 0;
        foreach ($configs as $type => $config) {
            $block = $bytype[$type] ?? null;
            if (!$block instanceof \stdClass) {
                continue;
            }
            if (!$this->block_config_is_empty((string)$block->configjson)) {
                $preserved++;
                continue;
            }

            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => in_array($type, self::E3_ENABLED_BLOCKS, true),
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $seeded++;
        }

        // Keep the editorial PPT order without deleting or duplicating custom blocks.
        $fresh = $this->showrooms->blocks($showroomid);
        $idsbytype = [];
        foreach ($fresh as $block) {
            $type = (string)$block->blocktype;
            if (!isset($idsbytype[$type])) {
                $idsbytype[$type] = (int)$block->id;
            }
        }
        $orderedids = [];
        foreach (self::E3_BLOCKS as $type) {
            if (isset($idsbytype[$type])) {
                $orderedids[] = $idsbytype[$type];
            }
        }
        foreach ($fresh as $block) {
            $id = (int)$block->id;
            if (!in_array($id, $orderedids, true)) {
                $orderedids[] = $id;
            }
        }
        $this->showrooms->reorder_blocks($showroomid, $orderedids, $userid);

        return ['seeded' => $seeded, 'created' => $created, 'preserved' => $preserved];
    }

    /**
     * 7.98E.4 — seeds the approved A1 programme (slide 9) and journey map (slide 10).
     *
     * Only empty programme/map blocks are populated. The existing E3 anchor
     * navigation is upgraded only while it still exactly matches the E3 seed,
     * so any Builder edit remains authoritative. Grammar content is deliberately
     * left empty because the supplied PPT only names the "Grammaire A1" tab and
     * does not provide its lesson list.
     *
     * @return array{seeded:int,preserved:int,anchorupdated:int}
     */
    public function apply_program_map_seed_v1(?int $actoruserid = null): array {
        $this->ensure_builder_structure($actoruserid);
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null || (string)$showroom->status !== 'draft') {
            return ['seeded' => 0, 'preserved' => 0, 'anchorupdated' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $bytype = [];
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $type = (string)$block->blocktype;
            if (!isset($bytype[$type])) {
                $bytype[$type] = $block;
            }
        }

        $seeded = 0;
        $preserved = 0;
        foreach ($this->program_map_seed_v1() as $type => $config) {
            $block = $bytype[$type] ?? null;
            if (!$block instanceof \stdClass) {
                continue;
            }
            if (!$this->block_config_is_empty((string)$block->configjson)) {
                $preserved++;
                continue;
            }

            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => true,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $seeded++;
        }

        $anchorupdated = 0;
        $anchor = $bytype['anchor_nav'] ?? null;
        if ($anchor instanceof \stdClass) {
            $current = json_decode((string)$anchor->configjson, true);
            $e3anchor = $this->editorial_seed_v1()['anchor_nav'] ?? [];
            if (is_array($current) && $current == $e3anchor) {
                $updated = $current;
                $updated['translations']['ru']['items'] =
                    "О курсе|#showroom-audience-profiles\n"
                    . "Как всё устроено|#showroom-adventure\n"
                    . "Программа|#showroom-program\n"
                    . "Тариф|#showroom-offers\n"
                    . "FAQ|#showroom-faq";
                $this->showrooms->save_block($showroomid, [
                    'id' => (int)$anchor->id,
                    'blocktype' => (string)$anchor->blocktype,
                    'blockkey' => (string)$anchor->blockkey,
                    'sortorder' => (int)$anchor->sortorder,
                    'enabled' => (int)$anchor->enabled === 1,
                    'configjson' => json_encode(
                        $updated,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                    ),
                ], $userid);
                $anchorupdated = 1;
            }
        }

        return [
            'seeded' => $seeded,
            'preserved' => $preserved,
            'anchorupdated' => $anchorupdated,
        ];
    }

    /**
     * 7.98E.5 — seeds the included A0 preparation story and the dynamic A1 offer.
     *
     * A0 remains editorial content inside A1: this method never creates or
     * attaches a second Commerce product. The offer configuration deliberately
     * contains no amount, currency or promotion dates; those values continue to
     * come from the Native Commerce / Pedagogy presenters at runtime. Existing
     * Builder edits remain authoritative.
     *
     * @return array{seeded:int,preserved:int}
     */
    public function apply_preparation_offer_seed_v1(?int $actoruserid = null): array {
        $this->ensure_builder_structure($actoruserid);
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null || (string)$showroom->status !== 'draft') {
            return ['seeded' => 0, 'preserved' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $bytype = [];
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $type = (string)$block->blocktype;
            if (!isset($bytype[$type])) {
                $bytype[$type] = $block;
            }
        }

        $seeded = 0;
        $preserved = 0;
        foreach ($this->preparation_offer_seed_v1() as $type => $config) {
            $block = $bytype[$type] ?? null;
            if (!$block instanceof \stdClass) {
                continue;
            }
            if (!$this->block_config_is_empty((string)$block->configjson)) {
                $preserved++;
                continue;
            }

            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => true,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $seeded++;
        }

        return ['seeded' => $seeded, 'preserved' => $preserved];
    }

    /**
     * 7.98E.6 — completes the review-ready Russian editorial baseline.
     *
     * The supplied PPT explicitly asks to reuse the verbs-course FAQ pattern,
     * but it does not contain testimonials. FAQ and final CTA are therefore
     * seeded and enabled, while the Testimonials block receives only its
     * heading and stays disabled until real customer quotes are supplied.
     * Existing Builder content and presentation choices always win.
     *
     * @return array{seeded:int,preserved:int,layoutmerged:int}
     */
    public function apply_review_ready_seed_v1(?int $actoruserid = null): array {
        $this->ensure_builder_structure($actoruserid);
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null || (string)$showroom->status !== 'draft') {
            return ['seeded' => 0, 'preserved' => 0, 'layoutmerged' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $bytype = [];
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $type = (string)$block->blocktype;
            if (!isset($bytype[$type])) {
                $bytype[$type] = $block;
            }
        }

        $seeded = 0;
        $preserved = 0;
        foreach ($this->review_ready_seed_v1() as $type => $config) {
            $block = $bytype[$type] ?? null;
            if (!$block instanceof \stdClass) {
                continue;
            }
            if (!$this->block_config_is_empty((string)$block->configjson)) {
                $preserved++;
                continue;
            }

            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => $type !== 'testimonials',
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $seeded++;
        }

        // Add only missing presentation defaults. Any choice already made in
        // the Builder is left byte-for-byte authoritative.
        $layoutmerged = 0;
        $freshbytype = [];
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $type = (string)$block->blocktype;
            if (!isset($freshbytype[$type])) {
                $freshbytype[$type] = $block;
            }
        }
        foreach ($this->review_layout_defaults_v1() as $type => $defaults) {
            $block = $freshbytype[$type] ?? null;
            if (!$block instanceof \stdClass) {
                continue;
            }
            $config = json_decode((string)$block->configjson, true);
            if (!is_array($config)) {
                $config = [];
            }
            $changed = false;
            foreach ($defaults as $field => $value) {
                if (!array_key_exists($field, $config)) {
                    $config[$field] = $value;
                    $changed = true;
                }
            }
            if (!$changed) {
                continue;
            }
            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
            $layoutmerged++;
        }

        $this->reorder_editorial_blocks($showroomid, $userid);

        return [
            'seeded' => $seeded,
            'preserved' => $preserved,
            'layoutmerged' => $layoutmerged,
        ];
    }

    /**
     * 7.98E.7 — fills missing FR/EN copy, SEO and draft public slugs.
     *
     * This remains a review preparation step, not a publication step. The
     * Showroom stays in draft. Existing translations, SEO fields and slugs are
     * never overwritten; only missing values are completed.
     *
     * @return array{translationsmerged:int,seofieldsset:int,slugsset:int}
     */
    public function apply_multilingual_seo_seed_v1(?int $actoruserid = null): array {
        $this->ensure_builder_structure($actoruserid);
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null || (string)$showroom->status !== 'draft') {
            return ['translationsmerged' => 0, 'seofieldsset' => 0, 'slugsset' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $translationsmerged = 0;
        $copy = $this->multilingual_copy_v1();

        foreach ($this->showrooms->blocks($showroomid) as $block) {
            $type = (string)$block->blocktype;
            if (!isset($copy[$type])) {
                continue;
            }
            $config = json_decode((string)$block->configjson, true);
            if (!is_array($config)) {
                $config = [];
            }
            if (!isset($config['translations']) || !is_array($config['translations'])) {
                $config['translations'] = [];
            }

            $changed = false;
            foreach (['fr', 'en'] as $language) {
                if (!isset($config['translations'][$language]) || !is_array($config['translations'][$language])) {
                    $config['translations'][$language] = [];
                }
                foreach ($copy[$type][$language] ?? [] as $field => $value) {
                    // E7.1: curriculum grammar is an intentional structural field.
                    // The supplied PPT names the A1 grammar tab but does not provide
                    // its syllabus yet. Keep the key explicitly present with an empty
                    // value so Builder/runtime consumers can rely on a stable schema.
                    if (trim((string)$value) === '') {
                        if ($type === 'curriculum_tabs' && $field === 'grammar'
                                && !array_key_exists($field, $config['translations'][$language])) {
                            $config['translations'][$language][$field] = '';
                            $translationsmerged++;
                            $changed = true;
                        }
                        continue;
                    }
                    $current = trim((string)($config['translations'][$language][$field] ?? ''));
                    if ($current !== '') {
                        continue;
                    }
                    $config['translations'][$language][$field] = $value;
                    $translationsmerged++;
                    $changed = true;
                }
            }

            if (!$changed) {
                continue;
            }
            $this->showrooms->save_block($showroomid, [
                'id' => (int)$block->id,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
            ], $userid);
        }

        $settings = json_decode((string)$showroom->settingsjson, true);
        if (!is_array($settings)) {
            $settings = [];
        }
        if (!isset($settings['seo']) || !is_array($settings['seo'])) {
            $settings['seo'] = [];
        }

        $seofieldsset = 0;
        foreach ($this->seo_seed_v1() as $language => $fields) {
            if (!isset($settings['seo'][$language]) || !is_array($settings['seo'][$language])) {
                $settings['seo'][$language] = [];
            }
            foreach ($fields as $field => $value) {
                if (trim((string)($settings['seo'][$language][$field] ?? '')) !== '') {
                    continue;
                }
                $settings['seo'][$language][$field] = $value;
                $seofieldsset++;
            }
        }

        $slugs = [
            'slugfr' => 'francais-a1',
            'slugen' => 'french-a1',
            'slugru' => 'francuzskij-a1',
        ];
        $slugsset = 0;
        foreach ($slugs as $field => $value) {
            if (trim((string)($showroom->{$field} ?? '')) === '') {
                $showroom->{$field} = $value;
                $slugsset++;
            }
        }

        $foundation = isset($settings['foundation']) && is_array($settings['foundation'])
            ? $settings['foundation']
            : [];
        if (empty($foundation['reviewready'])) {
            $foundation['reviewready'] = '7.98E7';
        }
        $settings['foundation'] = $foundation;

        $this->showrooms->save([
            'id' => $showroomid,
            'showroomkey' => (string)$showroom->showroomkey,
            'status' => 'draft',
            'name' => (string)$showroom->name,
            'template' => (string)$showroom->template,
            'slugfr' => (string)$showroom->slugfr,
            'slugen' => (string)$showroom->slugen,
            'slugru' => (string)$showroom->slugru,
            'titlekey' => (string)$showroom->titlekey,
            'descriptionkey' => (string)$showroom->descriptionkey,
            'productsjson' => (string)$showroom->productsjson,
            'settingsjson' => json_encode(
                $settings,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ], $userid);

        $this->reorder_editorial_blocks($showroomid, $userid);

        return [
            'translationsmerged' => $translationsmerged,
            'seofieldsset' => $seofieldsset,
            'slugsset' => $slugsset,
        ];
    }

    /**
     * 7.98E.7.2 — binds the A1 Showroom to the two explicit A1 Native products.
     *
     * Existing unrelated/custom roles are preserved, while the obsolete
     * single "course" relationship is removed. The offer block is migrated
     * from the historical single-card seed to a two-card presentation without
     * overwriting any already-authored course_fr/course_ru editorial fields.
     *
     * @return array{productsbound:int,offersettingsbound:int,offerblockupdated:int}
     */
    public function apply_dual_offer_product_binding_v1(?int $actoruserid = null): array {
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null) {
            $this->ensure($actoruserid);
            $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        }
        if ($showroom === null) {
            return ['productsbound' => 0, 'offersettingsbound' => 0, 'offerblockupdated' => 0];
        }

        $showroomid = (int)$showroom->id;
        $userid = max(0, (int)($actoruserid ?? 0));
        $products = json_decode((string)$showroom->productsjson, true);
        $products = is_array($products) ? $products : [];
        unset($products['course']);

        $productsbound = 0;
        foreach ([
            self::PRODUCT_ROLE_FR => self::PRODUCT_SKU_FR,
            self::PRODUCT_ROLE_RU => self::PRODUCT_SKU_RU,
        ] as $role => $sku) {
            if (($products[$role] ?? null) !== $sku) {
                $products[$role] = $sku;
                $productsbound++;
            }
        }
        $orderedproducts = [
            self::PRODUCT_ROLE_FR => self::PRODUCT_SKU_FR,
            self::PRODUCT_ROLE_RU => self::PRODUCT_SKU_RU,
        ];
        foreach ($products as $role => $sku) {
            if (!array_key_exists($role, $orderedproducts)) {
                $orderedproducts[$role] = $sku;
            }
        }

        $settings = json_decode((string)$showroom->settingsjson, true);
        $settings = is_array($settings) ? $settings : [];
        $settings['offers'] = isset($settings['offers']) && is_array($settings['offers'])
            ? $settings['offers']
            : [];
        $legacyoffer = isset($settings['offers']['course']) && is_array($settings['offers']['course'])
            ? $settings['offers']['course']
            : [];
        unset($settings['offers']['course']);

        $offersettingsbound = 0;
        foreach ([self::PRODUCT_ROLE_FR, self::PRODUCT_ROLE_RU] as $role) {
            $current = isset($settings['offers'][$role]) && is_array($settings['offers'][$role])
                ? $settings['offers'][$role]
                : [];
            $target = array_replace([
                'detailsenabled' => (bool)($legacyoffer['detailsenabled'] ?? false),
                'donationmode' => 'none',
            ], $current);
            $target['donationmode'] = 'none';
            if (($settings['offers'][$role] ?? null) !== $target) {
                $settings['offers'][$role] = $target;
                $offersettingsbound++;
            }
        }

        $settings['foundation'] = isset($settings['foundation']) && is_array($settings['foundation'])
            ? $settings['foundation']
            : [];
        $settings['foundation']['productbinding'] = '7.98E7.2';
        $settings['foundation']['a1products'] = [
            self::PRODUCT_ROLE_FR => self::PRODUCT_SKU_FR,
            self::PRODUCT_ROLE_RU => self::PRODUCT_SKU_RU,
        ];

        $this->showrooms->save([
            'id' => $showroomid,
            'showroomkey' => (string)$showroom->showroomkey,
            'status' => (string)$showroom->status,
            'name' => (string)$showroom->name,
            'template' => (string)$showroom->template,
            'slugfr' => (string)$showroom->slugfr,
            'slugen' => (string)$showroom->slugen,
            'slugru' => (string)$showroom->slugru,
            'titlekey' => (string)$showroom->titlekey,
            'descriptionkey' => (string)$showroom->descriptionkey,
            'productsjson' => json_encode(
                $orderedproducts,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
            'settingsjson' => json_encode(
                $settings,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ], $userid);

        $offerblockupdated = 0;
        foreach ($this->showrooms->blocks($showroomid) as $block) {
            if ((string)$block->blocktype !== 'offers') {
                continue;
            }
            $config = json_decode((string)$block->configjson, true);
            $config = is_array($config) ? $config : [];
            $before = $config;

            if (($config['presentation'] ?? '') === 'single_course'
                    && ($config['singleofferrole'] ?? 'course') === 'course') {
                $config['presentation'] = 'standard';
                unset($config['singleofferrole']);
            }
            if (!isset($config['order']) || trim((string)$config['order']) === ''
                    || trim((string)$config['order']) === 'course') {
                $config['order'] = self::PRODUCT_ROLE_FR . ',' . self::PRODUCT_ROLE_RU;
            }
            if (($config['featuredrole'] ?? '') === 'course') {
                $config['featuredrole'] = self::PRODUCT_ROLE_FR;
            }
            if (!isset($config['columns']) || (string)$config['columns'] === '') {
                $config['columns'] = '2';
            }

            if (!isset($config['translations']) || !is_array($config['translations'])) {
                $config['translations'] = [];
            }
            foreach (['ru', 'fr', 'en'] as $language) {
                if (!isset($config['translations'][$language]) || !is_array($config['translations'][$language])) {
                    $config['translations'][$language] = [];
                }
                $translation =& $config['translations'][$language];
                $labels = $this->dual_offer_labels($language);
                foreach ([self::PRODUCT_ROLE_FR, self::PRODUCT_ROLE_RU] as $role) {
                    $prefix = $role;
                    if (trim((string)($translation[$prefix . 'rolelabel'] ?? '')) === '') {
                        $translation[$prefix . 'rolelabel'] = $labels[$role]['rolelabel'];
                    }
                    if (trim((string)($translation[$prefix . 'subtitle'] ?? '')) === '') {
                        $translation[$prefix . 'subtitle'] = $labels[$role]['subtitle'];
                    }
                    foreach (['description', 'features', 'buylabel'] as $field) {
                        $targetkey = $prefix . $field;
                        $legacykey = 'course' . $field;
                        if (trim((string)($translation[$targetkey] ?? '')) === ''
                                && trim((string)($translation[$legacykey] ?? '')) !== '') {
                            $translation[$targetkey] = $translation[$legacykey];
                        }
                    }
                }
                unset($translation);
            }

            if ($config !== $before) {
                $this->showrooms->save_block($showroomid, [
                    'id' => (int)$block->id,
                    'blocktype' => (string)$block->blocktype,
                    'blockkey' => (string)$block->blockkey,
                    'sortorder' => (int)$block->sortorder,
                    'enabled' => (int)$block->enabled === 1,
                    'configjson' => json_encode(
                        $config,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                    ),
                ], $userid);
                $offerblockupdated = 1;
            }
            break;
        }

        return [
            'productsbound' => $productsbound,
            'offersettingsbound' => $offersettingsbound,
            'offerblockupdated' => $offerblockupdated,
        ];
    }

    /** @return array<string,array{rolelabel:string,subtitle:string}> */
    /**
     * 7.98E.7.3 — adds the short /a1 marketing alias without replacing the
     * language-specific canonical routes used for SEO/hreflang.
     *
     * If an operator previously entered "a1" in one or more canonical A1 slug
     * fields during DEV, those exact placeholder values are normalised back to
     * the approved language routes. Any other manual canonical slug or custom
     * short alias is preserved.
     *
     * @return array{aliasset:int,canonicalslugsnormalised:int}
     */
    public function apply_short_alias_v1(?int $actoruserid = null): array {
        $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        if ($showroom === null) {
            $this->ensure($actoruserid);
            $showroom = $this->showrooms->get_by_key(self::SHOWROOM_KEY);
        }
        if ($showroom === null) {
            return ['aliasset' => 0, 'canonicalslugsnormalised' => 0];
        }

        $userid = max(0, (int)($actoruserid ?? 0));
        $canonical = [
            'slugfr' => 'francais-a1',
            'slugen' => 'french-a1',
            'slugru' => 'francuzskij-a1',
        ];
        $canonicalslugsnormalised = 0;
        foreach ($canonical as $field => $value) {
            $current = trim((string)($showroom->{$field} ?? ''));
            if ($current === '' || CommerceShowroomAliasConfig::clean($current) === 'a1') {
                if ($current !== $value) {
                    $showroom->{$field} = $value;
                    $canonicalslugsnormalised++;
                }
            }
        }

        $settingsjson = (string)$showroom->settingsjson;
        $currentalias = CommerceShowroomAliasConfig::from_settings_json($settingsjson);
        $aliasset = 0;
        if ($currentalias === '') {
            $settingsjson = CommerceShowroomAliasConfig::merge_into_settings_json(
                $settingsjson,
                'a1'
            );
            $aliasset = 1;
        }

        $settings = json_decode($settingsjson, true);
        $settings = is_array($settings) ? $settings : [];
        $settings['foundation'] = isset($settings['foundation']) && is_array($settings['foundation'])
            ? $settings['foundation']
            : [];
        if (empty($settings['foundation']['shortalias'])) {
            $settings['foundation']['shortalias'] = '7.98E7.3';
        }

        $this->showrooms->save([
            'id' => (int)$showroom->id,
            'showroomkey' => (string)$showroom->showroomkey,
            'status' => (string)$showroom->status,
            'name' => (string)$showroom->name,
            'template' => (string)$showroom->template,
            'slugfr' => (string)$showroom->slugfr,
            'slugen' => (string)$showroom->slugen,
            'slugru' => (string)$showroom->slugru,
            'titlekey' => (string)$showroom->titlekey,
            'descriptionkey' => (string)$showroom->descriptionkey,
            'productsjson' => (string)$showroom->productsjson,
            'settingsjson' => json_encode(
                $settings,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ], $userid);

        return [
            'aliasset' => $aliasset,
            'canonicalslugsnormalised' => $canonicalslugsnormalised,
        ];
    }

    private function dual_offer_labels(string $language): array {
        return match ($language) {
            'fr' => [
                self::PRODUCT_ROLE_FR => [
                    'rolelabel' => 'ACCOMPAGNEMENT EN FRANÇAIS',
                    'subtitle' => 'Parcours A1 avec accompagnement en français',
                ],
                self::PRODUCT_ROLE_RU => [
                    'rolelabel' => 'ACCOMPAGNEMENT EN RUSSE',
                    'subtitle' => 'Parcours A1 avec accompagnement en russe',
                ],
            ],
            'en' => [
                self::PRODUCT_ROLE_FR => [
                    'rolelabel' => 'FRENCH-LANGUAGE SUPPORT',
                    'subtitle' => 'A1 journey with support in French',
                ],
                self::PRODUCT_ROLE_RU => [
                    'rolelabel' => 'RUSSIAN-LANGUAGE SUPPORT',
                    'subtitle' => 'A1 journey with support in Russian',
                ],
            ],
            default => [
                self::PRODUCT_ROLE_FR => [
                    'rolelabel' => 'СОПРОВОЖДЕНИЕ НА ФРАНЦУЗСКОМ',
                    'subtitle' => 'Курс A1 с сопровождением на французском языке',
                ],
                self::PRODUCT_ROLE_RU => [
                    'rolelabel' => 'СОПРОВОЖДЕНИЕ НА РУССКОМ',
                    'subtitle' => 'Курс A1 с сопровождением на русском языке',
                ],
            ],
        };
    }

    private function reorder_editorial_blocks(int $showroomid, int $userid): void {
        $blocks = $this->showrooms->blocks($showroomid);
        $idsbytype = [];
        foreach ($blocks as $block) {
            $type = (string)$block->blocktype;
            if (!isset($idsbytype[$type])) {
                $idsbytype[$type] = (int)$block->id;
            }
        }

        $orderedids = [];
        foreach (self::E3_BLOCKS as $type) {
            if (isset($idsbytype[$type])) {
                $orderedids[] = $idsbytype[$type];
            }
        }
        foreach ($blocks as $block) {
            $id = (int)$block->id;
            if (!in_array($id, $orderedids, true)) {
                $orderedids[] = $id;
            }
        }
        $this->showrooms->reorder_blocks($showroomid, $orderedids, $userid);
    }

    /** @return array<string,array<string,mixed>> */
    private function review_ready_seed_v1(): array {
        $ru = static fn(array $fields): array => ['translations' => ['ru' => $fields]];

        return [
            'testimonials' => $ru([
                'eyebrow' => 'ОТЗЫВЫ',
                'title' => 'Что говорят ученики CampusFR',
                'items' => '',
            ]),
            'faq' => $ru([
                'eyebrow' => 'FAQ',
                'title' => 'Остались вопросы?',
                'titleaccent' => 'Разберёмся',
                'text' => 'Собрали самые частые вопросы о курсе A1, формате обучения и доступе.',
                'items' => implode("\n", [
                    'Для кого подходит курс A1?|Курс создан для начинающих с нуля и для тех, кто уже немного учил французский, но хочет заново собрать базу и начать говорить увереннее.|fa-regular fa-circle-question',
                    'Что входит в курс?|30 приключений по Провансу, Лазурному берегу и Корсике: грамматика, тексты, аудирование, письмо, устная речь, интерактивная практика, общение с тьютором и поддержка преподавателя CampusFR.|fa-regular fa-circle-question',
                    'Что такое подготовительная часть A0?|A0 уже включена в A1 и открывается после покупки. Это подготовительная часть, которая помогает разобраться с чтением по-французски до старта основной программы. Покупать её отдельно не нужно.|fa-regular fa-circle-question',
                    'Как проходит обучение?|Курс открывается постепенно: вы проходите приключения по порядку, практикуете материал и двигаетесь вместе со своим потоком. Актуальные даты старта и доступности всегда показываются на этой странице.|fa-regular fa-circle-question',
                    'Чем тьютор отличается от преподавателя?|Французский тьютор общается с вашим кланом в чате и помогает использовать язык в живых ситуациях. Преподаватель CampusFR отвечает на вопросы по грамматике, заданиям и организации обучения.|fa-regular fa-circle-question',
                    'Что такое клан?|Это небольшая группа учеников одного потока. У вас есть общий чат, общие цели и возможность поддерживать друг друга на протяжении всех 30 приключений.|fa-regular fa-circle-question',
                    'Останется ли доступ к материалам?|Да. Материалы самого курса A1 остаются доступны после прохождения программы; сопровождение клана и тьютора относится к конкретному потоку.|fa-regular fa-circle-question',
                    'Как я получу доступ после покупки?|После оплаты курс автоматически появится в вашем CampusFR, а подтверждение покупки придёт по электронной почте. Если старт потока ещё впереди, подготовительная часть A0 уже будет доступна.|fa-regular fa-circle-question',
                    'Можно ли учиться с телефона?|Да. CampusFR адаптирован для компьютера, планшета и смартфона, поэтому к урокам и практике можно возвращаться с удобного устройства.|fa-regular fa-circle-question',
                ]),
            ]) + [
                'presentation' => 'standard',
                'singleopen' => true,
            ],
            'final_cta' => $ru([
                'eyebrow' => 'Et si on partait ensemble ?',
                'title' => 'Франция ждёт —',
                'titleaccent' => 'поехали вместе?',
                'text' => 'Твоё первое приключение уже рядом — осталось только сделать шаг.',
                'handnote' => 'On y va ?',
                'buttonlabel' => 'Начать путешествие',
            ]) + [
                'presentation' => 'standard',
                'titlesize' => 'normal',
                'badgedisplay' => 'show',
                'textdisplay' => 'show',
                'perksdisplay' => 'hide',
                'handnotedisplay' => 'show',
                'buttontarget' => '#showroom-offers',
                'style' => 'primary',
            ],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function review_layout_defaults_v1(): array {
        return [
            'hero' => ['sectionbackground' => 'white', 'sectionspacing' => 'normal', 'sectionanimation' => 'none'],
            'stats' => ['sectionbackground' => 'white', 'sectionspacing' => 'compact', 'sectionanimation' => 'none'],
            'anchor_nav' => ['sectionbackground' => 'white', 'sectionspacing' => 'compact', 'sectionanimation' => 'none'],
            'audience_profiles' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'adventure_overview' => ['sectionbackground' => 'light', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'content_highlights' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'community_clan' => ['sectionbackground' => 'campuspink', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'tutor_chat' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'support' => ['sectionbackground' => 'light', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'video' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'curriculum_tabs' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'journey_map' => ['sectionbackground' => 'light', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'preparation_flow' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'offers' => ['sectionbackground' => 'soft', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'testimonials' => ['sectionbackground' => 'campuspink', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'faq' => ['sectionbackground' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'rise'],
            'final_cta' => ['sectionbackground' => 'gradient', 'sectiontextcolor' => 'white', 'sectionspacing' => 'large', 'sectionanimation' => 'fade'],
        ];
    }

    /** @return array<string,array<string,array<string,string>>> */
    private function multilingual_copy_v1(): array {
        $fr = [];
        $en = [];

        $fr['hero'] = [
            'eyebrow' => 'Nouveau cours A1',
            'title' => 'Le français à partir de zéro —',
            'titleaccent' => 'un voyage en Provence, sur la Côte d’Azur et en Corse',
            'text' => '30 aventures, beaucoup de pratique, l’accompagnement d’un tuteur français et tout ce qu’il faut pour commencer à parler français avec confiance !',
            'primarylabel' => 'Commencer le voyage',
            'secondarylabel' => 'Voir la vidéo du cours',
        ];
        $en['hero'] = [
            'eyebrow' => 'New A1 course',
            'title' => 'French from zero —',
            'titleaccent' => 'a journey through Provence, the French Riviera and Corsica',
            'text' => '30 engaging adventures, plenty of practice, support from a French tutor and everything you need to start speaking French with confidence!',
            'primarylabel' => 'Start the journey',
            'secondarylabel' => 'Watch the course video',
        ];

        $fr['stats'] = ['items' => "30 étapes|Un voyage complet dans le sud de la France et en Corse|fa-solid fa-location-dot\n{promotion_start}|Départ du voyage · 2 nouvelles étapes chaque semaine|fa-regular fa-calendar\nA0 dès l’achat|Apprenez à lire en français avant le démarrage|fa-solid fa-graduation-cap\nGroupe de {group_size} personnes|Votre chat et votre équipe tout au long du parcours|fa-solid fa-people-group\nTuteur français + professeur|Pratique et réponses à vos questions|fa-regular fa-comments"];
        $en['stats'] = ['items' => "30 stops|A complete journey through southern France and Corsica|fa-solid fa-location-dot\n{promotion_start}|Journey starts · 2 new stops every week|fa-regular fa-calendar\nA0 from day one|Learn to read French before the main course begins|fa-solid fa-graduation-cap\nGroup of {group_size}|Your chat and team throughout the journey|fa-solid fa-people-group\nFrench tutor + teacher|Practice and answers to your course questions|fa-regular fa-comments"];

        $fr['anchor_nav'] = ['items' => "Le cours|#showroom-audience-profiles\nComment ça marche|#showroom-adventure\nProgramme|#showroom-program\nTarif|#showroom-offers\nFAQ|#showroom-faq", 'ctalabel' => 'Commencer le voyage'];
        $en['anchor_nav'] = ['items' => "The course|#showroom-audience-profiles\nHow it works|#showroom-adventure\nProgramme|#showroom-program\nPricing|#showroom-offers\nFAQ|#showroom-faq", 'ctalabel' => 'Start the journey'];

        $fr['audience_profiles'] = [
            'title' => 'À qui s’adresse', 'titleaccent' => 'ce cours',
            'text' => 'Nous avons créé ce cours pour celles et ceux qui veulent commencer à parler français et se sentir plus à l’aise en France et en voyage.',
            'items' => "Pour celles et ceux qui partent de zéro|Si vous n’avez jamais appris le français et souhaitez avancer calmement, étape par étape.|fa-solid fa-book-open\nPour celles et ceux qui ont appris mais ne parlent pas encore|Si vous aviez des bases mais souhaitez tout remettre en ordre et enfin commencer à parler.|fa-regular fa-comments\nPour celles et ceux qui vivent en France ou préparent leur installation|Pour gérer plus facilement les situations du quotidien et gagner en confiance chaque jour.|fa-solid fa-house-flag\nPour celles et ceux qui rêvent de voyager en France|Pour comprendre le français dans les transports, en ville, au café et dans les magasins.|fa-solid fa-suitcase-rolling",
        ];
        $en['audience_profiles'] = [
            'title' => 'Who is', 'titleaccent' => 'this course for?',
            'text' => 'We created this course for anyone who wants to start speaking French and feel more confident in France and while travelling.',
            'items' => "Complete beginners|If you have never studied French and want to start calmly, step by step.|fa-solid fa-book-open\nLearners who studied before but never started speaking|If you once had some basics and now want to rebuild them and finally speak.|fa-regular fa-comments\nPeople living in France or preparing to move|If you want everyday situations to feel easier and more natural.|fa-solid fa-house-flag\nPeople who dream of travelling around France|If you want to understand French on trips, in town, on transport, in cafés and shops.|fa-solid fa-suitcase-rolling",
        ];

        $fr['adventure_overview'] = [
            'title' => 'Ce ne sont pas 30 leçons ordinaires —', 'titleaccent' => 'ce sont 30 aventures',
            'text' => 'Dans chaque aventure, vous travaillez le thème dans son ensemble : grammaire, texte, écoute, écriture, expression orale et mini-histoire avec Gustave.',
            'centerlabel' => 'Aventure 1 · Nice',
            'items' => "Grammaire|vidéo + pratique|fa-solid fa-book-open\nTravail sur le texte|vocabulaire + texte audio|fa-regular fa-file-lines\nCompréhension orale|enregistrements d’acteurs français|fa-solid fa-headphones\nTemps libre|immersion dans le lieu du voyage|fa-solid fa-umbrella-beach\nExpression écrite|vous écrivez, l’IA vous aide|fa-solid fa-pen\nExpression orale|dialogues et monologues|fa-solid fa-microphone\nGustave en direct|mini-histoire dans la messagerie|fa-regular fa-comments\nVocabulaire supplémentaire|pour aller plus loin dans le thème|fa-regular fa-star",
            'note' => 'Petits pas, grandes découvertes ♥',
        ];
        $en['adventure_overview'] = [
            'title' => 'These are not 30 ordinary lessons —', 'titleaccent' => 'they are 30 adventures',
            'text' => 'In every adventure you explore the topic from every angle: grammar, reading, listening, writing, speaking and a mini-story with Gustave.',
            'centerlabel' => 'Adventure 1 · Nice',
            'items' => "Grammar|video + practice|fa-solid fa-book-open\nReading|vocabulary + narrated text|fa-regular fa-file-lines\nListening|recordings by French actors|fa-solid fa-headphones\nFree time|immerse yourself in the destination|fa-solid fa-umbrella-beach\nWriting|you write, AI helps|fa-solid fa-pen\nSpeaking|dialogues and monologues|fa-solid fa-microphone\nGustave on the ground|a mini-story in the messenger|fa-regular fa-comments\nExtra vocabulary|when you want to explore the topic further|fa-regular fa-star",
            'note' => 'Petits pas, grandes découvertes ♥',
        ];

        $fr['content_highlights'] = [
            'title' => 'Un français vraiment utile', 'titleaccent' => 'dans la vie',
            'text' => 'Dès la première aventure, vous apprenez la langue à travers des situations réelles qui vous attendent en France et en voyage.',
            'items' => "Au café et au restaurant|Commander, comprendre le menu, parler avec le serveur.|fa-solid fa-mug-hot\nDans les trains et les transports|Acheter un billet, comprendre un trajet, demander son chemin.|fa-solid fa-train\nDans les magasins et sur les marchés|Choisir, demander un prix, parler avec un vendeur.|fa-solid fa-bag-shopping\nPour faire connaissance et discuter|Se présenter, entretenir une conversation, rencontrer des gens.|fa-solid fa-user-group\nEn ville et en voyage|Demander son chemin, trouver un lieu, gérer une situation.|fa-solid fa-location-dot\nDans la vie quotidienne|Résoudre les petites situations de tous les jours avec plus d’assurance.|fa-solid fa-house",
        ];
        $en['content_highlights'] = [
            'title' => 'French you will actually use', 'titleaccent' => 'in real life',
            'text' => 'From the very first adventure, you learn French through real situations you will meet in France and while travelling.',
            'items' => "In cafés and restaurants|Order food, understand a menu and speak to a waiter.|fa-solid fa-mug-hot\nOn trains and public transport|Buy a ticket, understand a route and ask for directions.|fa-solid fa-train\nIn shops and markets|Choose products, ask prices and speak to a seller.|fa-solid fa-bag-shopping\nMeeting people and socialising|Introduce yourself, keep a conversation going and get to know people.|fa-solid fa-user-group\nIn town and while travelling|Ask for directions, find the right place and handle a situation.|fa-solid fa-location-dot\nEveryday life|Deal with daily situations and feel more confident.|fa-solid fa-house",
        ];

        $fr['community_clan'] = [
            'eyebrow' => '05', 'title' => 'Vous aurez', 'titleaccent' => 'votre propre clan',
            'text' => 'Vous suivez le cours dans un petit groupe de {group_size} personnes. Vous apprenez ensemble, partagez vos impressions et avancez à travers les 30 aventures du début à la fin.',
            'items' => "{group_size} personnes dans le clan|Une petite équipe conviviale où chacun peut s’exprimer et être entendu.|fa-solid fa-people-group\nVotre propre chat|Posez des questions, partagez vos impressions et vos photos, encouragez-vous.|fa-regular fa-comments\nDes objectifs communs|Avancez ensemble à travers les 30 aventures et gardez votre motivation.|fa-solid fa-flag\nDes clans uniques|Chaque promotion a ses propres noms de clans : croissants, lavande, explorateurs et bien d’autres.|fa-solid fa-trophy",
            'buttonlabel' => 'Rejoindre le clan',
        ];
        $en['community_clan'] = [
            'eyebrow' => '05', 'title' => 'You will have', 'titleaccent' => 'your own clan',
            'text' => 'You take the course in a small group of {group_size}. You learn together, share your experiences and complete all 30 adventures from start to finish.',
            'items' => "{group_size} people in your clan|A small friendly team where everyone can speak and be heard.|fa-solid fa-people-group\nYour own chat|Ask questions, share impressions and photos, and support one another.|fa-regular fa-comments\nShared goals|Complete all 30 adventures together and help each other stay motivated.|fa-solid fa-flag\nUnique clans|Every cohort has its own clan names: croissants, lavender, explorers and more.|fa-solid fa-trophy",
            'buttonlabel' => 'Join the clan',
        ];

        $fr['tutor_chat'] = [
            'eyebrow' => '06', 'title' => 'Un tuteur français — comme', 'titleaccent' => 'un ami français dans votre chat',
            'text' => 'Chaque clan est accompagné par un vrai Français qui échange avec vous dans le chat au quotidien, simplement et naturellement. Vous parlez de la vie, partagez vos impressions, posez des questions et recevez des photos, audios et vidéos de France.',
            'features' => "Échanges vivants|Écrivez, discutez et posez vos questions sur tous les sujets.|fa-regular fa-comment-dots\nLa vraie vie|Photos, audios et vidéos de France : rues, cafés, voyages, quotidien.|fa-solid fa-camera\nPratique sans stress|Utilisez le français appris dans des échanges naturels.|fa-regular fa-star\nMotivation au quotidien|Un Français à vos côtés pour rendre l’apprentissage vivant et motivant.|fa-regular fa-heart",
            'messages' => "Tuteur|Salut tout le monde ! 🇫🇷 Aujourd’hui je suis à Nice ☀️ Je prends un café face à la mer. Et vous, quoi de prévu ce week-end ?|tutor\nÉlève|Waouh ! 😍 Ça a l’air incroyable ! Moi, je vais au marché demain !|student\nTuteur|Super ! Tu vas acheter quoi ? Des fruits ? Des légumes ?|tutor",
            'buttonlabel' => 'Découvrir le tuteur',
        ];
        $en['tutor_chat'] = [
            'eyebrow' => '06', 'title' => 'A French tutor — like', 'titleaccent' => 'a French friend in your chat',
            'text' => 'Every clan is accompanied by a real French person who chats with you naturally throughout the week. You talk about life, share experiences, ask questions and receive photos, audio and video from France.',
            'features' => "Real conversation|Write, discuss and ask questions about everyday topics.|fa-regular fa-comment-dots\nReal life|Photos, audio and video from France: streets, cafés, trips and daily life.|fa-solid fa-camera\nLow-pressure practice|Use the French you have learned in natural conversation.|fa-regular fa-star\nDaily motivation|A French person alongside you to make learning lively and motivating.|fa-regular fa-heart",
            'messages' => "Tutor|Salut tout le monde ! 🇫🇷 Aujourd’hui je suis à Nice ☀️ Je prends un café face à la mer. Et vous, quoi de prévu ce week-end ?|tutor\nStudent|Waouh ! 😍 Ça a l’air incroyable ! Moi, je vais au marché demain !|student\nTutor|Super ! Tu vas acheter quoi ? Des fruits ? Des légumes ?|tutor",
            'buttonlabel' => 'Meet the tutor',
        ];

        $fr['support'] = [
            'title' => 'Un professeur CampusFR à vos côtés quand vous en avez besoin',
            'text' => 'Si quelque chose n’est pas clair pendant le parcours, vous ne restez pas seul face au cours. Le professeur répond aux questions de grammaire, d’exercices et d’organisation et vous aide à continuer sereinement.',
            'buttonlabel' => 'Poser une question au professeur',
        ];
        $en['support'] = [
            'title' => 'A CampusFR teacher is there when you need help',
            'text' => 'If something is unclear during the journey, you are not left alone with the course. The teacher answers questions about grammar, exercises and learning and helps you move forward with confidence.',
            'buttonlabel' => 'Ask the teacher a question',
        ];

        $fr['video'] = [
            'eyebrow' => '08', 'title' => 'Découvrez le cours', 'titleaccent' => 'de l’intérieur',
            'text' => 'En quelques minutes, découvrez CampusFR : la structure d’une aventure, les exercices proposés et la manière dont nous travaillons la grammaire, le vocabulaire, l’écoute, l’écrit et l’oral.',
            'note' => 'La vidéo vous montre concrètement l’intérieur de CampusFR.',
        ];
        $en['video'] = [
            'eyebrow' => '08', 'title' => 'See how the course works', 'titleaccent' => 'from the inside',
            'text' => 'In just a few minutes, see how CampusFR works: one complete adventure, the exercises you will do and how we train grammar, vocabulary, listening, writing and speaking.',
            'note' => 'The video gives you a real look inside CampusFR.',
        ];

        $fr['curriculum_tabs'] = [
            'eyebrow' => '09', 'title' => 'Ce que vous allez apprendre —', 'titleaccent' => 'le programme A1',
            'text' => '30 aventures pour construire pas à pas le français dont vous avez besoin dans la vraie vie.',
            'tab1label' => 'Thèmes du cours', 'tab2label' => 'Grammaire A1',
            'topics' => implode("\n", [
                'Premiers pas|fa-solid fa-people-group|01|Faire connaissance',
                'Premiers pas|fa-solid fa-people-group|02|Apparence et caractère',
                'Premiers pas|fa-solid fa-people-group|03|Pays et villes',
                'Premiers pas|fa-solid fa-people-group|04|Métiers et langues',
                'Premiers pas|fa-solid fa-people-group|05|Famille et animaux',
                'Chaque jour|fa-solid fa-house|06|Loisirs et centres d’intérêt',
                'Chaque jour|fa-solid fa-house|07|Maison et logement',
                'Chaque jour|fa-solid fa-house|08|Routine quotidienne',
                'Chaque jour|fa-solid fa-house|09|Heure et dates',
                'Chaque jour|fa-solid fa-house|10|Météo et saisons',
                'Ville et vie|fa-solid fa-location-dot|11|En ville',
                'Ville et vie|fa-solid fa-location-dot|12|Transports',
                'Ville et vie|fa-solid fa-location-dot|13|Achats',
                'Ville et vie|fa-solid fa-location-dot|14|Vêtements et chaussures',
                'Ville et vie|fa-solid fa-location-dot|15|Au café et au restaurant',
                'Communication|fa-regular fa-comment-dots|16|Alimentation et produits',
                'Communication|fa-regular fa-comment-dots|17|Santé',
                'Communication|fa-regular fa-comment-dots|18|Temps libre',
                'Communication|fa-regular fa-comment-dots|19|Amis et échanges',
                'Communication|fa-regular fa-comment-dots|20|Téléphone et messages',
                'Voyages|fa-solid fa-plane|21|À l’hôtel',
                'Voyages|fa-solid fa-plane|22|Voyage et trajet',
                'Voyages|fa-solid fa-plane|23|France et culture',
                'Voyages|fa-solid fa-plane|24|Fêtes et traditions',
                'Voyages|fa-solid fa-plane|25|Situations pratiques du quotidien',
                'Plus à l’aise|fa-regular fa-star|26|Événements passés',
                'Plus à l’aise|fa-regular fa-star|27|Projets futurs',
                'Plus à l’aise|fa-regular fa-star|28|Émotions et impressions',
                'Plus à l’aise|fa-regular fa-star|29|Révision et pratique',
                'Plus à l’aise|fa-regular fa-star|30|Aventure finale',
            ]),
            'grammar' => '',
        ];
        $en['curriculum_tabs'] = [
            'eyebrow' => '09', 'title' => 'What you will learn —', 'titleaccent' => 'the A1 programme',
            'text' => '30 adventures that build practical French step by step for real life.',
            'tab1label' => 'Course topics', 'tab2label' => 'A1 grammar',
            'topics' => implode("\n", [
                'First steps|fa-solid fa-people-group|01|Meeting people',
                'First steps|fa-solid fa-people-group|02|Appearance and personality',
                'First steps|fa-solid fa-people-group|03|Countries and cities',
                'First steps|fa-solid fa-people-group|04|Jobs and languages',
                'First steps|fa-solid fa-people-group|05|Family and pets',
                'Everyday life|fa-solid fa-house|06|Hobbies and interests',
                'Everyday life|fa-solid fa-house|07|Home and housing',
                'Everyday life|fa-solid fa-house|08|Daily routine',
                'Everyday life|fa-solid fa-house|09|Time and dates',
                'Everyday life|fa-solid fa-house|10|Weather and seasons',
                'Town and life|fa-solid fa-location-dot|11|In town',
                'Town and life|fa-solid fa-location-dot|12|Transport',
                'Town and life|fa-solid fa-location-dot|13|Shopping',
                'Town and life|fa-solid fa-location-dot|14|Clothes and shoes',
                'Town and life|fa-solid fa-location-dot|15|At a café and restaurant',
                'Communication|fa-regular fa-comment-dots|16|Food and groceries',
                'Communication|fa-regular fa-comment-dots|17|Health',
                'Communication|fa-regular fa-comment-dots|18|Free time',
                'Communication|fa-regular fa-comment-dots|19|Friends and socialising',
                'Communication|fa-regular fa-comment-dots|20|Phone and messages',
                'Travel|fa-solid fa-plane|21|At the hotel',
                'Travel|fa-solid fa-plane|22|Travelling and getting around',
                'Travel|fa-solid fa-plane|23|France and culture',
                'Travel|fa-solid fa-plane|24|Festivals and traditions',
                'Travel|fa-solid fa-plane|25|Useful everyday situations',
                'More confident|fa-regular fa-star|26|Past events',
                'More confident|fa-regular fa-star|27|Future plans',
                'More confident|fa-regular fa-star|28|Emotions and impressions',
                'More confident|fa-regular fa-star|29|Review and practice',
                'More confident|fa-regular fa-star|30|Final adventure',
            ]),
            'grammar' => '',
        ];

        $fr['journey_map'] = [
            'eyebrow' => '10', 'title' => 'La carte du voyage —', 'titleaccent' => 'où allez-vous partir ?',
            'text' => 'Vous voyagez à travers la Provence, la Côte d’Azur et la Corse : de nouveaux lieux, l’atmosphère du sud et des pauses pour découvrir plus en profondeur la culture, l’histoire et la vie en France.',
            'stops' => "Avignon|Provence\nAix-en-Provence|Provence\nMarseille|Provence\nNice|Côte d’Azur\nAntibes|Côte d’Azur\nCannes|Côte d’Azur\nBastia|Corse\nAjaccio|Corse\nBonifacio|Corse",
            'items' => "Côte d’Azur|Nice, Antibes, Cannes et d’autres villes au bord de la mer.|fa-solid fa-umbrella-beach\nProvence|Marseille, Aix-en-Provence, Avignon, lavande, marchés et ambiance du sud.|fa-solid fa-seedling\nCorse|Bastia, Ajaccio, Bonifacio et une aventure insulaire au milieu du parcours.|fa-solid fa-sailboat\nTemps libre|De petites parenthèses détendues pour approfondir la culture, l’histoire et la vie de la région.|fa-solid fa-camera",
            'note' => 'On y va ?',
        ];
        $en['journey_map'] = [
            'eyebrow' => '10', 'title' => 'Your journey map —', 'titleaccent' => 'where will you go?',
            'text' => 'Travel through Provence, the French Riviera and Corsica: discover new places, absorb the atmosphere of southern France and use relaxed breaks to explore its culture, history and daily life.',
            'stops' => "Avignon|Provence\nAix-en-Provence|Provence\nMarseille|Provence\nNice|French Riviera\nAntibes|French Riviera\nCannes|French Riviera\nBastia|Corsica\nAjaccio|Corsica\nBonifacio|Corsica",
            'items' => "French Riviera|Nice, Antibes, Cannes and other seaside towns.|fa-solid fa-umbrella-beach\nProvence|Marseille, Aix-en-Provence, Avignon, lavender, markets and southern atmosphere.|fa-solid fa-seedling\nCorsica|Bastia, Ajaccio, Bonifacio and an island adventure halfway through the journey.|fa-solid fa-sailboat\nFree time|Relaxed sections where you discover more about the region’s culture, history and everyday life.|fa-solid fa-camera",
            'note' => 'On y va ?',
        ];

        $fr['preparation_flow'] = [
            'eyebrow' => '11', 'title' => 'Avant le départ —', 'titleaccent' => 'l’aventure préparatoire A0',
            'text' => 'Dès l’achat, la partie préparatoire A0 s’ouvre à l’intérieur du cours A1. En attendant le démarrage du parcours principal, vous apprenez à lire en français et arrivez en A1 plus sereinement.',
            'items' => "Vous achetez le cours A1|Un seul achat : A0 n’est pas un produit séparé.|fa-solid fa-cart-shopping\nA0 s’ouvre immédiatement|La partie préparatoire est déjà incluse dans A1.|fa-solid fa-graduation-cap\nVous apprenez à lire en français|Vous découvrez les règles de lecture essentielles avant le programme principal.|fa-solid fa-book-open\nVous commencez A1 plus sereinement|Au démarrage du voyage, la lecture française vous est déjà plus familière.|fa-solid fa-flag",
            'note' => 'A0 est inclus dans le cours A1 et n’est pas vendu séparément.',
        ];
        $en['preparation_flow'] = [
            'eyebrow' => '11', 'title' => 'Before the start —', 'titleaccent' => 'your A0 preparation adventure',
            'text' => 'Right after purchase, the A0 preparation section opens inside the A1 course. While you wait for the main journey to begin, you learn how to read French and arrive at A1 with more confidence.',
            'items' => "Buy the A1 course|One purchase only: A0 is not a separate product.|fa-solid fa-cart-shopping\nGet A0 immediately|The preparation section is already included in A1.|fa-solid fa-graduation-cap\nLearn to read French|Discover the essential reading rules before the main programme.|fa-solid fa-book-open\nStart A1 with more confidence|By the time the journey begins, French reading already feels more familiar.|fa-solid fa-flag",
            'note' => 'A0 is included in the A1 course and is not sold separately.',
        ];

        $fr['offers'] = [
            'badge' => 'TARIF', 'title' => 'Le tarif de votre', 'titlehighlight' => 'voyage',
            'text' => 'Une seule formule avec tout ce qu’il faut pour commencer à parler français avec confiance et se sentir plus à l’aise en France.',
            'courserolelabel' => 'LE VOYAGE COMPLET', 'coursetitle' => 'Cours A1 CampusFR',
            'coursedescription' => '30 aventures en Provence, sur la Côte d’Azur et en Corse. Tout ce qu’il faut pour atteindre le niveau A1.',
            'coursefeatures' => "30 aventures en Provence, sur la Côte d’Azur et en Corse\nPréparation A0 dès l’achat\nTuteur français dans le chat\nClan de {group_size} personnes + professeur CampusFR\nGrammaire, textes, écoute, écrit et oral\nExercices interactifs et pratique\nAccès durable aux contenus",
            'coursebuylabel' => 'Rejoindre le cours',
        ];
        $en['offers'] = [
            'badge' => 'PRICE', 'title' => 'Your journey', 'titlehighlight' => 'package',
            'text' => 'One complete package with everything you need to start speaking French with confidence and feel more at home in France.',
            'courserolelabel' => 'THE COMPLETE JOURNEY', 'coursetitle' => 'CampusFR A1 course',
            'coursedescription' => '30 adventures through Provence, the French Riviera and Corsica. Everything you need to reach A1 level.',
            'coursefeatures' => "30 adventures through Provence, the French Riviera and Corsica\nA0 preparation from day one\nFrench tutor in the chat\nClan of {group_size} + CampusFR teacher\nGrammar, reading, listening, writing and speaking\nInteractive exercises and practice\nLong-term access to course content",
            'coursebuylabel' => 'Join the course',
        ];

        $fr['testimonials'] = ['eyebrow' => 'TÉMOIGNAGES', 'title' => 'Ce que disent les élèves CampusFR', 'items' => ''];
        $en['testimonials'] = ['eyebrow' => 'REVIEWS', 'title' => 'What CampusFR students say', 'items' => ''];

        $fr['faq'] = [
            'eyebrow' => 'FAQ', 'title' => 'Encore des questions ?', 'titleaccent' => 'On vous répond',
            'text' => 'Les réponses aux questions les plus fréquentes sur le cours A1, l’accompagnement et l’accès.',
            'items' => implode("\n", [
                'À qui s’adresse le cours A1 ?|Aux grands débutants comme aux personnes qui ont déjà étudié un peu de français mais souhaitent reprendre leurs bases et commencer à parler avec davantage d’assurance.|fa-regular fa-circle-question',
                'Que contient le cours ?|30 aventures en Provence, sur la Côte d’Azur et en Corse : grammaire, textes, compréhension orale, écrit, oral, pratique interactive, tuteur français et accompagnement CampusFR.|fa-regular fa-circle-question',
                'Qu’est-ce que la préparation A0 ?|A0 est déjà inclus dans A1 et s’ouvre après l’achat. Cette partie préparatoire vous aide à comprendre la lecture française avant le démarrage du programme principal. Aucun achat séparé n’est nécessaire.|fa-regular fa-circle-question',
                'Comment se déroule le parcours ?|Le cours s’ouvre progressivement : vous avancez aventure après aventure avec votre promotion. Les dates actuelles de démarrage et de disponibilité sont toujours affichées sur cette page.|fa-regular fa-circle-question',
                'Quelle différence entre le tuteur et le professeur ?|Le tuteur français échange avec votre clan et vous fait pratiquer dans des situations vivantes. Le professeur CampusFR répond aux questions de grammaire, d’exercices et d’organisation du parcours.|fa-regular fa-circle-question',
                'Qu’est-ce qu’un clan ?|C’est votre petit groupe au sein de la promotion, avec son chat, ses objectifs communs et la possibilité de s’encourager pendant les 30 aventures.|fa-regular fa-circle-question',
                'L’accès aux contenus reste-t-il disponible ?|Oui. Les contenus du cours A1 restent accessibles après le parcours ; l’accompagnement du clan et du tuteur est lié à la promotion suivie.|fa-regular fa-circle-question',
                'Comment accéder au cours après l’achat ?|Après le paiement, le cours apparaît automatiquement dans votre CampusFR et vous recevez une confirmation par e-mail. Si la promotion n’a pas encore démarré, la préparation A0 est déjà accessible.|fa-regular fa-circle-question',
                'Puis-je suivre le cours sur téléphone ?|Oui. CampusFR est adapté à l’ordinateur, à la tablette et au smartphone.|fa-regular fa-circle-question',
            ]),
        ];
        $en['faq'] = [
            'eyebrow' => 'FAQ', 'title' => 'Still have questions?', 'titleaccent' => 'Here are the answers',
            'text' => 'Answers to the most common questions about the A1 course, support and access.',
            'items' => implode("\n", [
                'Who is the A1 course for?|It is designed for complete beginners and for people who studied some French before but want to rebuild the basics and start speaking with more confidence.|fa-regular fa-circle-question',
                'What is included in the course?|30 adventures through Provence, the French Riviera and Corsica: grammar, reading, listening, writing, speaking, interactive practice, a French tutor and CampusFR teacher support.|fa-regular fa-circle-question',
                'What is the A0 preparation section?|A0 is already included in A1 and opens after purchase. It helps you understand French reading before the main programme begins. There is nothing extra to buy.|fa-regular fa-circle-question',
                'How does the learning journey work?|The course opens progressively as you move from one adventure to the next with your cohort. Current start and availability dates are always shown on this page.|fa-regular fa-circle-question',
                'What is the difference between the tutor and the teacher?|The French tutor chats with your clan and helps you use French in real situations. The CampusFR teacher answers questions about grammar, exercises and the learning process.|fa-regular fa-circle-question',
                'What is a clan?|It is your small group within the cohort, with a shared chat, common goals and a team to support you throughout all 30 adventures.|fa-regular fa-circle-question',
                'Do I keep access to the course content?|Yes. The A1 course content remains available after the programme; clan and tutor support belong to the specific cohort you joined.|fa-regular fa-circle-question',
                'How do I get access after purchase?|After payment, the course appears automatically in your CampusFR account and you receive an email confirmation. If your cohort has not started yet, the A0 preparation section is already available.|fa-regular fa-circle-question',
                'Can I study on my phone?|Yes. CampusFR is designed for computers, tablets and smartphones.|fa-regular fa-circle-question',
            ]),
        ];

        $fr['final_cta'] = [
            'eyebrow' => 'Et si on partait ensemble ?', 'title' => 'La France vous attend —',
            'titleaccent' => 'on y va ensemble ?', 'text' => 'Votre première aventure est déjà toute proche : il ne reste plus qu’à faire le premier pas.',
            'handnote' => 'On y va ?', 'buttonlabel' => 'Commencer le voyage',
        ];
        $en['final_cta'] = [
            'eyebrow' => 'What if we set off together?', 'title' => 'France is waiting —',
            'titleaccent' => 'shall we go together?', 'text' => 'Your first adventure is already close — all that is left is to take the first step.',
            'handnote' => 'On y va ?', 'buttonlabel' => 'Start the journey',
        ];

        $result = [];
        foreach (array_unique(array_merge(array_keys($fr), array_keys($en))) as $type) {
            $result[$type] = [
                'fr' => $fr[$type] ?? [],
                'en' => $en[$type] ?? [],
            ];
        }
        return $result;
    }

    /** @return array<string,array<string,string>> */
    private function seo_seed_v1(): array {
        return [
            'ru' => [
                'title' => 'Французский A1 с нуля — курс CampusFR',
                'description' => '30 приключений для уровня A1: грамматика, тексты, аудирование, устная практика, французский тьютор, клан и подготовительная часть A0.',
                'socialtitle' => 'Курс французского A1 — путешествие с CampusFR',
                'socialdescription' => 'Французский с нуля через 30 приключений по Провансу, Лазурному берегу и Корсике.',
                'keywords' => 'французский A1, французский с нуля, курс французского, CampusFR',
            ],
            'fr' => [
                'title' => 'Cours de français A1 pour débutants | CampusFR',
                'description' => '30 aventures pour apprendre le français A1 : grammaire, textes, écoute, oral, tuteur français, petit groupe et préparation A0 incluse.',
                'socialtitle' => 'Cours de français A1 — le voyage CampusFR',
                'socialdescription' => 'Apprenez le français depuis zéro à travers 30 aventures en Provence, sur la Côte d’Azur et en Corse.',
                'keywords' => 'cours français A1, français débutant, apprendre français, CampusFR',
            ],
            'en' => [
                'title' => 'French A1 course for beginners | CampusFR',
                'description' => '30 adventures to learn A1 French: grammar, reading, listening, speaking, a French tutor, a small group and included A0 preparation.',
                'socialtitle' => 'French A1 course — the CampusFR journey',
                'socialdescription' => 'Learn French from zero through 30 adventures in Provence, the French Riviera and Corsica.',
                'keywords' => 'French A1 course, beginner French, learn French, CampusFR',
            ],
        ];
    }

    private function block_config_is_empty(string $json): bool {
        $decoded = json_decode($json, true);
        return !is_array($decoded) || $decoded === [];
    }

    /** @return array<string,array<string,mixed>> */
    private function preparation_offer_seed_v1(): array {
        $ru = static fn(array $fields): array => ['translations' => ['ru' => $fields]];

        return [
            'preparation_flow' => $ru([
                'eyebrow' => '11',
                'title' => 'До старта —',
                'titleaccent' => 'подготовительное приключение A0',
                'text' => 'Сразу после покупки тебе открывается подготовительная часть A0 внутри курса A1. Пока ты ждёшь начала основного путешествия, ты учишься читать по-французски и входишь в A1 увереннее.',
                'items' => implode("\n", [
                    'Покупаешь курс A1|Оформляешь один курс — без отдельной покупки A0.|fa-solid fa-cart-shopping',
                    'Сразу получаешь A0|Подготовительная часть уже включена в A1 и доступна после покупки.|fa-solid fa-graduation-cap',
                    'Учишься читать по-французски|Разбираешь базовые правила чтения до старта основной программы.|fa-solid fa-book-open',
                    'Входишь в A1 увереннее|К началу путешествия уже ориентируешься во французском чтении.|fa-solid fa-flag',
                ]),
                'note' => 'A0 включён в курс A1 и не продаётся отдельно.',
            ]),
            'offers' => $ru([
                'badge' => 'ТАРИФ',
                'title' => 'Тариф твоего',
                'titlehighlight' => 'путешествия',
                'text' => 'Один тариф — всё, что нужно, чтобы с нуля уверенно заговорить по-французски и почувствовать себя как дома во Франции.',
                'course_frrolelabel' => 'СОПРОВОЖДЕНИЕ НА ФРАНЦУЗСКОМ',
                'course_frsubtitle' => 'Французский тьютор и сопровождение на французском',
                'course_frdescription' => '30 увлекательных уроков в формате путешествия по Провансу, Лазурному берегу и Корсике. Всё, что нужно, чтобы достичь уровня A1.',
                'course_frfeatures' => implode("\n", [
                    '30 приключений по Провансу, Лазурному берегу и Корсике',
                    'Подготовительная часть A0 сразу после покупки',
                    'Французский тьютор в чате',
                    'Клан из {group_size} человек + преподаватель CampusFR',
                    'Грамматика, тексты, аудирование, письмо и устная речь',
                    'Интерактивные задания и практика',
                    'Доступ к материалам навсегда',
                ]),
                'course_frbuylabel' => 'Выбрать',
                'course_rurolelabel' => 'СОПРОВОЖДЕНИЕ НА РУССКОМ',
                'course_rusubtitle' => 'Сопровождение и помощь на русском языке',
                'course_rudescription' => '30 увлекательных уроков в формате путешествия по Провансу, Лазурному берегу и Корсике. Всё, что нужно, чтобы достичь уровня A1.',
                'course_rufeatures' => implode("\n", [
                    '30 приключений по Провансу, Лазурному берегу и Корсике',
                    'Подготовительная часть A0 сразу после покупки',
                    'Сопровождение на русском языке',
                    'Клан из {group_size} человек + преподаватель CampusFR',
                    'Грамматика, тексты, аудирование, письмо и устная речь',
                    'Интерактивные задания и практика',
                    'Доступ к материалам навсегда',
                ]),
                'course_rubuylabel' => 'Выбрать',
            ]) + [
                // Two Native A1 products share the same Moodle course. Pricing,
                // promotion_join and capacity stay runtime-driven by Commerce.
                'presentation' => 'standard',
                'order' => self::PRODUCT_ROLE_FR . ',' . self::PRODUCT_ROLE_RU,
                'featuredrole' => self::PRODUCT_ROLE_FR,
                'showsavings' => true,
                'showbadges' => false,
                'cardvariant' => 'premium',
                'columns' => '2',
                'mobilepresentation' => 'stack',
                'cardstopspacing' => 'normal',
            ],
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function program_map_seed_v1(): array {
        $ru = static fn(array $fields): array => ['translations' => ['ru' => $fields]];

        $topics = implode("\n", [
            'Первые шаги|fa-solid fa-people-group|01|Знакомство',
            'Первые шаги|fa-solid fa-people-group|02|Внешность и характер',
            'Первые шаги|fa-solid fa-people-group|03|Страны и города',
            'Первые шаги|fa-solid fa-people-group|04|Профессии и языки',
            'Первые шаги|fa-solid fa-people-group|05|Семья и питомцы',
            'Каждый день|fa-solid fa-house|06|Хобби и интересы',
            'Каждый день|fa-solid fa-house|07|Дом и жильё',
            'Каждый день|fa-solid fa-house|08|Распорядок дня',
            'Каждый день|fa-solid fa-house|09|Время и даты',
            'Каждый день|fa-solid fa-house|10|Погода и сезоны',
            'Город и жизнь|fa-solid fa-location-dot|11|В городе',
            'Город и жизнь|fa-solid fa-location-dot|12|Транспорт',
            'Город и жизнь|fa-solid fa-location-dot|13|Покупки',
            'Город и жизнь|fa-solid fa-location-dot|14|Одежда и обувь',
            'Город и жизнь|fa-solid fa-location-dot|15|В кафе и ресторане',
            'Общение|fa-regular fa-comment-dots|16|Еда и продукты',
            'Общение|fa-regular fa-comment-dots|17|Здоровье',
            'Общение|fa-regular fa-comment-dots|18|Свободное время',
            'Общение|fa-regular fa-comment-dots|19|Друзья и общение',
            'Общение|fa-regular fa-comment-dots|20|Телефон и сообщения',
            'Путешествия|fa-solid fa-plane|21|В отеле',
            'Путешествия|fa-solid fa-plane|22|Путешествия и дорога',
            'Путешествия|fa-solid fa-plane|23|Франция и культура',
            'Путешествия|fa-solid fa-plane|24|Праздники и традиции',
            'Путешествия|fa-solid fa-plane|25|Полезные бытовые ситуации',
            'Уже увереннее|fa-regular fa-star|26|Прошедшие события',
            'Уже увереннее|fa-regular fa-star|27|Планы на будущее',
            'Уже увереннее|fa-regular fa-star|28|Эмоции и впечатления',
            'Уже увереннее|fa-regular fa-star|29|Повторение и практика',
            'Уже увереннее|fa-regular fa-star|30|Финальное приключение',
        ]);

        return [
            'curriculum_tabs' => $ru([
                'eyebrow' => '09',
                'title' => 'Что ты изучишь —',
                'titleaccent' => 'программа A1',
                'text' => '30 приключений, в которых ты шаг за шагом строишь французский для реальной жизни.',
                'tab1label' => 'Темы курса',
                'tab2label' => 'Грамматика A1',
                'topics' => $topics,
                // The PPT exposes the tab label but does not provide the grammar syllabus.
                'grammar' => '',
            ]) + [
                'defaulttab' => 'topics',
            ],
            'journey_map' => $ru([
                'eyebrow' => '10',
                'title' => 'Карта путешествия —',
                'titleaccent' => 'куда ты отправишься',
                'text' => 'Ты будешь погружаться в путешествие по Провансу, Лазурному берегу и Корсике: открывать новые места, знакомиться с атмосферой региона и использовать моменты свободное время, чтобы узнать культуру, историю и жизнь Франции ближе.',
                'stops' => implode("\n", [
                    'Avignon|Прованс',
                    'Aix-en-Provence|Прованс',
                    'Marseille|Прованс',
                    'Nice|Лазурный берег',
                    'Antibes|Лазурный берег',
                    'Cannes|Лазурный берег',
                    'Bastia|Корсика',
                    'Ajaccio|Корсика',
                    'Bonifacio|Корсика',
                ]),
                'items' => implode("\n", [
                    'Лазурный берег|Nice, Antibes, Cannes и другие города у моря.|fa-solid fa-umbrella-beach',
                    'Прованс|Marseille, Aix-en-Provence, Avignon, лаванда, рынки и южная атмосфера.|fa-solid fa-seedling',
                    'Корсика|Bastia, Ajaccio, Bonifacio и островное приключение в середине пути.|fa-solid fa-sailboat',
                    'Свободное время|Небольшие расслабленные разделы в курсе, где ты глубже узнаёшь культуру, историю и жизнь региона.|fa-solid fa-camera',
                ]),
                'note' => 'On y va ?',
            ]),
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function editorial_seed_v1(): array {
        $ru = static fn(array $fields): array => ['translations' => ['ru' => $fields]];

        return [
            'hero' => $ru([
                'eyebrow' => 'Новый курс A1',
                'title' => 'Французский с нуля —',
                'titleaccent' => 'путешествие по Провансу, Лазурному берегу и Корсике',
                'text' => '30 увлекательных уроков, живая практика, поддержка французского тьютора и всё, чтобы уверенно начать говорить по-французски!',
                'primarylabel' => 'Начать путешествие',
                'secondarylabel' => 'Смотреть видео о курсе',
            ]) + [
                'primarytarget' => '#showroom-offers',
                'secondarytarget' => '#showroom-video',
                'showvideobutton' => true,
                'herovariant' => 'split',
                'mediaposition' => 'right',
                'showgustave' => false,
            ],
            'stats' => $ru([
                'items' => "30 остановок|Полное путешествие по югу Франции и Корсике|fa-solid fa-location-dot\n{promotion_start}|Старт путешествия · 2 новые остановки каждую неделю|fa-regular fa-calendar\nA0 сразу после покупки|До старта научишься читать по-французски|fa-solid fa-graduation-cap\nКлан из {group_size} человек|Свой чат и команда на всём пути|fa-solid fa-people-group\nФранцузский тьютор + преподаватель|Практика и ответы на вопросы по курсу|fa-regular fa-comments",
            ]) + [
                'layout' => 'bar',
                'animate' => false,
            ],
            'anchor_nav' => $ru([
                'items' => "О курсе|#showroom-audience-profiles\nКак всё устроено|#showroom-adventure\nТариф|#showroom-offers\nFAQ|#showroom-faq",
                'ctalabel' => 'Начать путешествие',
            ]) + [
                'sticky' => true,
                'ctatarget' => '#showroom-offers',
            ],
            'audience_profiles' => $ru([
                'title' => 'Кому подойдёт',
                'titleaccent' => 'этот курс',
                'text' => 'Мы создали этот курс для тех, кто хочет начать говорить по-французски и чувствовать себя увереннее во Франции и в путешествиях.',
                'items' => "Для тех, кто начинает с нуля|Если ты ещё не учил(а) французский и хочешь начать спокойно, шаг за шагом.|fa-solid fa-book-open\nДля тех, кто учил, но не заговорил|Если база когда-то была, но теперь хочется всё собрать заново и наконец начать говорить.|fa-regular fa-comments\nДля тех, кто живёт во Франции или готовится к переезду|Если хочешь легче справляться с повседневными ситуациями и чувствовать себя увереннее каждый день.|fa-solid fa-house-flag\nДля тех, кто мечтает путешествовать по Франции|Если хочешь понимать французский в поездках, в городе, на транспорте, в кафе и в магазинах.|fa-solid fa-suitcase-rolling",
            ]),
            'adventure_overview' => $ru([
                'title' => 'Это не 30 обычных уроков —',
                'titleaccent' => 'это 30 приключений',
                'text' => 'В каждом приключении ты проходишь тему полностью: смотришь грамматику, работаешь с текстом, слушаешь французов, пишешь, говоришь и проходишь мини-историю с Гюставом.',
                'centerlabel' => 'Приключение 1 · Nice',
                'items' => "Грамматика|видео + практика|fa-solid fa-book-open\nРабота с текстом|лексика + текст с озвучкой|fa-regular fa-file-lines\nАудирование|записи французских актёров|fa-solid fa-headphones\nСвободное время|погружение в место путешествия|fa-solid fa-umbrella-beach\nПисьменная речь|пишешь сам, ИИ помогает|fa-solid fa-pen\nУстная речь|диалоги и монологи|fa-solid fa-microphone\nГюстав на связи|мини-история в мессенджере|fa-regular fa-comments\nДополнительная лексика|если хочешь раскрыть тему шире|fa-regular fa-star",
                'note' => 'Petits pas, grandes découvertes ♥',
            ]),
            'content_highlights' => $ru([
                'title' => 'Французский, который пригодится',
                'titleaccent' => 'в жизни',
                'text' => 'С первого приключения ты учишь язык в реальных ситуациях, которые ждут тебя во Франции и в путешествиях.',
                'items' => "В кафе и ресторанах|Заказать еду, понять меню, пообщаться с официантом.|fa-solid fa-mug-hot\nВ поездах и на транспорте|Купить билет, узнать маршрут, спросить дорогу.|fa-solid fa-train\nВ магазинах и на рынках|Выбрать товары, узнать цены, поговорить с продавцом.|fa-solid fa-bag-shopping\nПри знакомстве и в общении|Представиться, поддержать разговор, познакомиться.|fa-solid fa-user-group\nВ городе и в путешествии|Спросить дорогу, найти нужное место, разобраться в ситуации.|fa-solid fa-location-dot\nВ быту и каждый день|Решать повседневные вопросы и чувствовать себя увереннее.|fa-solid fa-house",
            ]) + [
                'presentation' => 'standard',
                'columns' => '3',
            ],
            'community_clan' => $ru([
                'eyebrow' => '05',
                'title' => 'У тебя будет',
                'titleaccent' => 'свой клан',
                'text' => 'Ты проходишь курс в небольшой группе из {group_size} человек. Вместе вы учитесь, делитесь впечатлениями, поддерживаете друг друга и проходите все 30 приключений от начала до конца.',
                'items' => "{group_size} человек в клане|Небольшая и дружная команда, где каждый может высказаться и быть услышанным.|fa-solid fa-people-group\nСвой чат|Общайтесь, задавайте вопросы, делитесь впечатлениями, отправляйте фото и поддерживайте друг друга.|fa-regular fa-comments\nОбщие цели|Вместе проходите все 30 приключений и мотивируйте друг друга не останавливаться.|fa-solid fa-flag\nУникальные кланы|У каждого потока — свои названия кланов: «Клан круассанов», «Клан лаванды», «Клан исследователей» и другие.|fa-solid fa-trophy",
                'buttonlabel' => 'Стать частью клана',
            ]) + [
                'buttontarget' => '#showroom-offers',
            ],
            'tutor_chat' => $ru([
                'eyebrow' => '06',
                'title' => 'Французский тьютор — как',
                'titleaccent' => 'французский друг в вашем чате',
                'text' => 'В каждом клане вас сопровождает настоящий француз, который общается с вами в чате каждый день — просто как с друзьями. Вы обсуждаете жизнь, делитесь впечатлениями, задаёте вопросы, получаете фото, аудио и видео из Франции и тренируете французский в реальных ситуациях.',
                'features' => "Живое общение|Пишите, обсуждайте, задавайте вопросы на любые темы.|fa-regular fa-comment-dots\nРеальная жизнь|Фото, аудио, видео из Франции: улицы, кафе, поездки, будни.|fa-solid fa-camera\nПрактика без стресса|Используете изученный французский в естественном общении.|fa-regular fa-star\nМотивация каждый день|Француз рядом, который поддерживает, вдохновляет и делает обучение живым и увлекательным.|fa-regular fa-heart",
                'messages' => "Тьютор|Salut tout le monde ! 🇫🇷 Aujourd’hui je suis à Nice ☀️ Je prends un café face à la mer. Et vous, quoi de prévu ce week-end ?|tutor\nУченик|Waouh ! 😍 Ça a l’air incroyable ! Moi, je vais au marché demain !|student\nТьютор|Super ! Tu vas acheter quoi ? Des fruits ? Des légumes ?|tutor",
                'buttonlabel' => 'Познакомиться с тьютором',
            ]) + [
                'buttontarget' => '#showroom-support',
            ],
            'support' => $ru([
                'title' => 'Преподаватель CampusFR — рядом, когда нужно',
                'text' => 'Если по ходу приключений что-то непонятно, вы не остаётесь один на один с курсом. Преподаватель отвечает на вопросы по грамматике, заданиям и самому обучению — и помогает двигаться дальше спокойно и уверенно.',
                'buttonlabel' => 'Задать вопрос преподавателю',
            ]),
            'video' => $ru([
                'eyebrow' => '08',
                'title' => 'Посмотри, как устроен курс',
                'titleaccent' => 'изнутри',
                'text' => 'За несколько минут покажу тебе CampusFR: как устроено одно приключение, какие задания тебя ждут и как мы тренируем грамматику, лексику, аудирование, письмо и устную речь.',
                'note' => 'В видео ты увидишь, как всё выглядит внутри CampusFR.',
            ]) + [
                'presentation' => 'standard',
                'ratio' => '16:9',
                'modal' => true,
            ],
        ];
    }

}
