<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontMerchandisingResolver;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;

/** Converts the CRM form into the safe Storefront metadata contract. */
final class CommerceStorefrontPageEditor {
    public const MAX_SECTIONS = 16;

    private const TEMPLATES = ['default', 'editorial', 'immersive'];
    private const SECTION_TYPES =
        \local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema::TYPES;

    /** @return array<string,mixed> */
    public function definition_from_product(CommerceProduct $product, ?string $language = null): array {
        $metadata = $product->get_metadata();
        $configuration = $metadata['storefront'] ?? [];

        if (is_string($configuration)) {
            $decoded = json_decode($configuration, true);
            $configuration = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($configuration)) {
            $configuration = [];
        }

        foreach (['template', 'theme', 'sections'] as $key) {
            $legacykey = 'storefront_' . $key;
            if (!array_key_exists($key, $configuration) && array_key_exists($legacykey, $metadata)) {
                $configuration[$key] = $metadata[$legacykey];
            }
        }

        $template = strtolower(trim((string)($configuration['template'] ?? 'default')));
        if (!in_array($template, self::TEMPLATES, true)) {
            $template = 'default';
        }

        $language = strtolower(trim($language ?: current_language()));
        $language = explode('_', str_replace('-', '_', $language))[0];
        $localized = is_array($configuration['locales'][$language] ?? null) ? $configuration['locales'][$language] : [];
        if (isset($localized['sections'])) {
            $configuration['sections'] = $localized['sections'];
        }
        if (isset($localized['seo']) && is_array($localized['seo'])) {
            $configuration['seo'] = $localized['seo'];
        }
        if (isset($localized['experience']) && is_array($localized['experience'])) {
            $configuration['experience'] = array_replace(is_array($configuration['experience'] ?? null) ? $configuration['experience'] : [], $localized['experience']);
            $metadata['storefront'] = $configuration;
        }

        $merchandising = (new CommerceStorefrontMerchandisingResolver())->resolve($metadata);
        $promotioneur = $merchandising->get_promotion('EUR');
        $promotionrub = $merchandising->get_promotion('RUB');
        $experience = (new CommerceStorefrontExperienceResolver())->resolve($metadata, $product->get_type());

        $shellmode = strtolower(trim((string)($configuration['shell_mode'] ?? 'standard')));
        if (!in_array($shellmode, ['standard', 'fullwidth', 'landing', 'immersive'], true)) {
            $shellmode = 'standard';
        }

        return [
            'template' => $template,
            'commerce_position' => \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::normalise_commerce_position(
                (string)($configuration['commerce_position'] ?? ''),
                $template === 'default' ? 'standard' : $template
            ),
            'shell_mode' => $shellmode,
            'show_header' => !array_key_exists('show_header', $configuration) || (bool)$configuration['show_header'],
            'show_footer' => !array_key_exists('show_footer', $configuration) || (bool)$configuration['show_footer'],
            'product_header_mode' => in_array(
                strtolower(trim((string)($configuration['product_header_mode'] ?? 'automatic'))),
                ['automatic', 'builder', 'hidden'],
                true
            ) ? strtolower(trim((string)($configuration['product_header_mode'] ?? 'automatic'))) : 'automatic',
            'global_zones' => \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::normalise_global_zones(
                $configuration['global_zones'] ?? []
            ),
            'theme' => $this->clean_theme((string)($configuration['theme'] ?? 'default')),
            'sections' => array_slice(
                is_array($configuration['sections'] ?? null) ? $configuration['sections'] : [],
                0,
                self::MAX_SECTIONS
            ),
            'featured' => $merchandising->is_featured(),
            'displayorder' => $merchandising->get_display_order(),
            'badges' => $merchandising->get_badges(),
            'promotion_eur_compare' => $this->minor_to_major($promotioneur['compareamountminor'] ?? null),
            'promotion_eur_start' => $this->date_value($promotioneur['start'] ?? null),
            'promotion_eur_end' => $this->date_value($promotioneur['end'] ?? null),
            'promotion_rub_compare' => $this->minor_to_major($promotionrub['compareamountminor'] ?? null),
            'promotion_rub_start' => $this->date_value($promotionrub['start'] ?? null),
            'promotion_rub_end' => $this->date_value($promotionrub['end'] ?? null),
            'group' => $experience->get_group(),
            'trust' => $experience->get_trust_items(),
            'quickfacts' => $this->quick_facts_to_text($experience->get_quick_facts()),
            'recommendations' => implode(
                PHP_EOL,
                (new \local_subscriptions\commerce\storefront\recommendation\CommerceStorefrontRecommendationResolver())
                    ->resolve($metadata)
            ),
            'seo_title' => trim((string)(
                $configuration['seo']['title'] ?? ''
            )),
            'seo_description' => trim((string)(
                $configuration['seo']['description'] ?? ''
            )),
            'route_slug_fr' => (string)($configuration['routing']['slugs']['fr'] ?? ''),
            'route_slug_en' => (string)($configuration['routing']['slugs']['en'] ?? ''),
            'route_slug_ru' => (string)($configuration['routing']['slugs']['ru'] ?? ''),
            'showroom_key' => (string)($metadata['showroom']['key'] ?? ''),
            'showroom_discoverymode' =>
                \local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver::normalise_mode(
                    (string)($metadata['showroom']['discoverymode'] ?? 'storefront')
                ),
            'showroom_showstorefrontcta' =>
                !array_key_exists('showstorefrontcta', (array)($metadata['showroom'] ?? []))
                || !empty($metadata['showroom']['showstorefrontcta']),
            'showroom_mediaitemid' => max(0, (int)($metadata['showroom']['mediaitemid'] ?? 0)),
            'showroom_alt' => (string)($metadata['showroom']['locales'][$language]['alt'] ?? ''),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function form_rows(CommerceProduct $product, ?string $language = null): array {
        $definition = $this->definition_from_product($product, $language);
        $rows = [];

        foreach ($definition['sections'] as $section) {
            if (!is_array($section)) {
                continue;
            }
            $type = strtolower(trim((string)($section['type'] ?? '')));
            if (!in_array($type, self::SECTION_TYPES, true)) {
                continue;
            }
            $rows[] = [
                'type' => $type,
                'id' => (string)($section['id'] ?? ''),
                'visible' => !array_key_exists('visible', $section)
                    || (bool)$section['visible'],
                'order' => (int)($section['order'] ?? count($rows) * 10),
                'style' => (string)($section['style'] ?? 'default'),
                'title' => (string)($section['title'] ?? ''),
                'subtitle' => (string)($section['subtitle'] ?? ''),
                'content' => $this->section_content($type, $section),
                'mediaitemid' => max(
                    0,
                    (int)($section['mediaitemid'] ?? 0)
                ),
                'imagemode' => (string)(
                    $section['imagemode'] ?? 'upload'
                ),
                'imageposition' => (string)(
                    $section['imageposition'] ?? 'left'
                ),
                'imagefit' => (string)(
                    $section['imagefit'] ?? 'cover'
                ),
                'columnratio' => (string)(
                    $section['columnratio'] ?? '50_50'
                ),
                'herolayout' => (string)(
                    $section['herolayout'] ?? 'text_media'
                ),
                'heroratio' => (string)(
                    $section['heroratio'] ?? '55_45'
                ),
                'heromediaratio' => (string)(
                    $section['heromediaratio'] ?? 'original'
                ),
                'videosource' => (string)(
                    $section['videosource'] ?? 'upload'
                ),
                'videoratio' => (string)(
                    $section['videoratio'] ?? '16_9'
                ),
                'h5pcontentid' => max(
                    0,
                    (int)($section['h5pcontentid'] ?? 0)
                ),
                'h5pheight' => max(
                    240,
                    min(1200, (int)($section['h5pheight'] ?? 640))
                ),
                'auxiliary' => $this->section_auxiliary($type, $section),
                'alt' => (string)($section['alt'] ?? ''),
                'items' => $this->section_items($type, $section),
                'presentation' => (string)($section['presentation'] ?? 'default'),
                'contentalignment' => $this->choice(
                    (string)($section['contentalignment'] ?? 'left'),
                    ['left', 'center', 'right'],
                    'left'
                ),
                'animation' => (string)($section['animation'] ?? 'none'),
                'layout' => \local_subscriptions\commerce\storefront\page\CommerceStorefrontComposerLayout::normalise(
                    $section,
                    count($rows)
                ),
            ];
        }

        while (count($rows) < self::MAX_SECTIONS) {
            $rows[] = [
                'type' => '',
                'id' => '',
                'visible' => true,
                'order' => count($rows) * 10,
                'style' => 'default',
                'title' => '',
                'subtitle' => '',
                'content' => '',
                'mediaitemid' => 0,
                'imagemode' => 'upload',
                'imageposition' => 'left',
                'imagefit' => 'cover',
                'columnratio' => '50_50',
                'herolayout' => 'text_media',
                'heroratio' => '55_45',
                'heromediaratio' => 'original',
                'videosource' => 'upload',
                'videoratio' => '16_9',
                'h5pcontentid' => 0,
                'h5pheight' => 640,
                'auxiliary' => '',
                'alt' => '',
                'items' => '',
                'presentation' => 'default',
                'contentalignment' => 'left',
                'animation' => 'none',
                'layout' => \local_subscriptions\commerce\storefront\page\CommerceStorefrontComposerLayout::normalise([], count($rows)),
            ];
        }
        return $rows;
    }

    /** @param array<string,mixed> $submitted @return array<string,mixed> */
    public function merge_submission(array $metadata, array $submitted, ?string $language = null): array {
        $template = strtolower(trim((string)($submitted['template'] ?? 'default')));
        if (!in_array($template, self::TEMPLATES, true)) {
            $template = 'default';
        }

        $sections = [];
        for ($index = 0; $index < self::MAX_SECTIONS; $index++) {
            $type = strtolower(trim((string)($submitted['section_type_' . $index] ?? '')));
            if (!in_array($type, self::SECTION_TYPES, true)) {
                continue;
            }

            $section = [
                'id' => $this->clean_section_id(
                    (string)($submitted['section_id_' . $index] ?? ''),
                    $index
                ),
                'type' => $type,
                'visible' => !empty(
                    $submitted['section_visible_' . $index]
                ),
                'order' => max(
                    0,
                    min(
                        9999,
                        (int)($submitted['section_order_' . $index]
                            ?? $index * 10)
                    )
                ),
                'style' => $this->clean_section_style(
                    (string)($submitted['section_style_' . $index]
                        ?? 'default')
                ),
                'title' => trim((string)($submitted['section_title_' . $index] ?? '')),
                'subtitle' => trim((string)($submitted['section_subtitle_' . $index] ?? '')),
                'presentation' => $this->choice(
                    (string)($submitted['section_presentation_' . $index] ?? 'default'),
                    ['default', 'split', 'overlay', 'cards', 'carousel', 'masonry', 'timeline', 'comparison', 'premium', 'statement', 'feature', 'commerce'],
                    'default'
                ),
                'contentalignment' => $this->choice(
                    (string)($submitted['section_content_alignment_' . $index] ?? 'left'),
                    ['left', 'center', 'right'],
                    'left'
                ),
                'animation' => $this->choice(
                    (string)($submitted['section_animation_' . $index] ?? 'none'),
                    ['none', 'fade', 'slide_up', 'zoom'],
                    'none'
                ),
                'layout' => \local_subscriptions\commerce\storefront\page\CommerceStorefrontComposerLayout::normalise([
                    'layout' => [
                        'rowid' => (string)($submitted['section_row_id_' . $index] ?? ''),
                        'column' => (int)($submitted['section_column_' . $index] ?? 1),
                        'columns' => (int)($submitted['section_columns_' . $index] ?? 1),
                        'ratio' => (string)($submitted['section_layout_ratio_' . $index] ?? '100'),
                        'width' => (string)($submitted['section_width_' . $index] ?? 'contained'),
                        'background' => (string)($submitted['section_background_' . $index] ?? 'default'),
                        'spacing' => (string)($submitted['section_spacing_' . $index] ?? 'medium'),
                        'alignment' => (string)($submitted['section_alignment_' . $index] ?? 'stretch'),
                    ],
                ], $index),
            ];
            $content = trim((string)($submitted['section_content_' . $index] ?? ''));
            $auxiliary = trim((string)($submitted['section_auxiliary_' . $index] ?? ''));
            $items = trim((string)($submitted['section_items_' . $index] ?? ''));

            switch ($type) {
                case 'hero':
                    $section['content'] = $content;
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted['section_content_itemid_' . $index] ?? 0)
                    );
                    $section['url'] = $auxiliary;
                    $section['alt'] = trim((string)(
                        $submitted['section_alt_' . $index] ?? ''
                    ));
                    $section['herolayout'] = $this->choice(
                        (string)($submitted['section_hero_layout_' . $index] ?? 'text_media'),
                        ['text_media', 'media_text', 'stacked', 'overlay'],
                        'text_media'
                    );
                    $section['heroratio'] = $this->choice(
                        (string)($submitted['section_hero_ratio_' . $index] ?? '55_45'),
                        ['50_50', '55_45', '60_40', '45_55'],
                        '55_45'
                    );
                    $section['heromediaratio'] = $this->choice(
                        (string)($submitted['section_hero_media_ratio_' . $index] ?? 'original'),
                        ['original', '1_1', '4_3', '16_9'],
                        'original'
                    );
                    break;
                case 'rich_text':
                    $section['content'] = $content;
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    break;
                case 'cta':
                    $section['content'] = $content;
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    break;
                case 'image_text':
                    $section['content'] = $content;
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    $section['url'] = $auxiliary;
                    $section['alt'] = trim((string)(
                        $submitted['section_alt_' . $index] ?? ''
                    ));
                    $section['imagemode'] = $this->choice(
                        (string)($submitted[
                            'section_image_mode_' . $index
                        ] ?? 'upload'),
                        ['upload', 'url'],
                        'upload'
                    );
                    $section['imageposition'] = $this->choice(
                        (string)($submitted[
                            'section_image_position_' . $index
                        ] ?? 'left'),
                        ['left', 'right'],
                        'left'
                    );
                    $section['imagefit'] = $this->choice(
                        (string)($submitted[
                            'section_image_fit_' . $index
                        ] ?? 'cover'),
                        ['cover', 'contain'],
                        'cover'
                    );
                    $section['columnratio'] = $this->choice(
                        (string)($submitted[
                            'section_column_ratio_' . $index
                        ] ?? '50_50'),
                        ['40_60', '50_50', '60_40'],
                        '50_50'
                    );
                    break;
                case 'video':
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    $section['url'] = $auxiliary;
                    $section['caption'] = $content;
                    $section['videosource'] = $this->choice(
                        (string)($submitted[
                            'section_video_source_' . $index
                        ] ?? 'upload'),
                        ['upload', 'youtube', 'vimeo', 'url'],
                        'upload'
                    );
                    $section['videoratio'] = $this->choice(
                        (string)($submitted[
                            'section_video_ratio_' . $index
                        ] ?? '16_9'),
                        ['16_9', '4_3', '1_1'],
                        '16_9'
                    );
                    break;
                case 'h5p':
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    $section['content'] = $content;
                    $section['url'] = $auxiliary;
                    $section['h5pcontentid'] = max(
                        0,
                        (int)($submitted[
                            'section_h5p_contentid_' . $index
                        ] ?? 0)
                    );
                    $section['h5pheight'] = max(
                        240,
                        min(
                            1200,
                            (int)($submitted[
                                'section_h5p_height_' . $index
                            ] ?? 640)
                        )
                    );
                    break;
                case 'features':
                    $section['content'] = $content;
                    $section['mediaitemid'] = max(
                        0,
                        (int)($submitted[
                            'section_content_itemid_' . $index
                        ] ?? 0)
                    );
                    $section['items'] = $this->parse_items(
                        $items,
                        'title',
                        'content'
                    );
                    break;
                case 'program':
                case 'timeline':
                case 'comparison':
                case 'accordion':
                    $section['items'] = $this->parse_items(
                        $items,
                        'title',
                        'content'
                    );
                    break;
                case 'media':
                    $section['url'] = $auxiliary;
                    $section['alt'] = trim((string)($submitted['section_alt_' . $index] ?? ''));
                    $section['caption'] = $content;
                    break;
                case 'instructor':
                    $section['name'] = trim((string)(
                        $submitted['section_title_' . $index] ?? ''
                    ));
                    $section['role'] = trim((string)(
                        $submitted['section_subtitle_' . $index] ?? ''
                    ));
                    $section['content'] = $content;
                    $section['url'] = $auxiliary;
                    $section['alt'] = trim((string)(
                        $submitted['section_alt_' . $index] ?? ''
                    ));
                    break;
                case 'testimonial':
                    $section['quote'] = $content;
                    $section['author'] = $auxiliary;
                    break;
                case 'testimonials':
                    $section['items'] = $this->parse_items(
                        $items,
                        'author',
                        'quote'
                    );
                    break;
                case 'faq':
                    $section['items'] = $this->parse_items(
                        $items,
                        'question',
                        'answer'
                    );
                    break;
                case 'gallery':
                    $section['items'] = $this->parse_gallery_items($items);
                    break;
                case 'components':
                    break;
            }

            $sections[] = array_filter(
                $section,
                static fn(mixed $value): bool => $value !== '' && $value !== []
            );
        }

        usort(
            $sections,
            static function (
                array $left,
                array $right
            ): int {
                $ordercomparison = (int)($left['order'] ?? 0)
                    <=> (int)($right['order'] ?? 0);
                if ($ordercomparison !== 0) {
                    return $ordercomparison;
                }

                return strcmp(
                    (string)($left['id'] ?? ''),
                    (string)($right['id'] ?? '')
                );
            }
        );
        foreach ($sections as $position => &$section) {
            $section['order'] = $position * 10;
        }
        unset($section);

        $existingstorefront = $metadata['storefront'] ?? [];
        if (!is_array($existingstorefront)) {
            $existingstorefront = [];
        }

        $language = strtolower(trim($language ?: current_language()));
        $language = explode('_', str_replace('-', '_', $language))[0];
        $existingsections = $existingstorefront['locales'][$language]['sections']
            ?? ($language === 'fr' ? ($existingstorefront['sections'] ?? []) : []);
        $sections = $this->preserve_durable_section_data(
            $sections,
            is_array($existingsections) ? $existingsections : []
        );

        $existingstorefront['schema_version'] =
            \local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema::VERSION;
        $existingstorefront['template'] = $template;
        $globalzones = \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::normalise_global_zones(
            $submitted['global_zones'] ?? []
        );
        $requestedposition = (string)($submitted['commerce_position'] ?? '');
        if (!in_array($requestedposition, [
            \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::HERO_INTEGRATED,
            \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::SIDEBAR_STICKY,
            \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::NONE,
        ], true)) {
            $requestedposition = \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::commerce_position_from_zones($globalzones);
        }
        $existingstorefront['commerce_position'] = \local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract::normalise_commerce_position(
            $requestedposition,
            $template === 'default' ? 'standard' : $template
        );
        $shellmode = strtolower(trim((string)($submitted['shell_mode'] ?? 'standard')));
        $existingstorefront['shell_mode'] = in_array($shellmode, ['standard', 'fullwidth', 'landing', 'immersive'], true)
            ? $shellmode
            : 'standard';
        $existingstorefront['show_header'] = !empty($submitted['show_header']);
        $existingstorefront['show_footer'] = !empty($submitted['show_footer']);
        $productheadermode = strtolower(trim((string)($submitted['product_header_mode'] ?? 'automatic')));
        $existingstorefront['product_header_mode'] = in_array(
            $productheadermode,
            ['automatic', 'builder', 'hidden'],
            true
        ) ? $productheadermode : 'automatic';
        $existingstorefront['global_zones'] = $globalzones;
        $existingstorefront['theme'] = $this->clean_theme((string)($submitted['theme'] ?? 'default'));
        $existingstorefront['locales'] = is_array($existingstorefront['locales'] ?? null) ? $existingstorefront['locales'] : [];
        $existingstorefront['locales'][$language] = is_array($existingstorefront['locales'][$language] ?? null) ? $existingstorefront['locales'][$language] : [];
        $existingstorefront['locales'][$language]['sections'] = $sections;
        $existingstorefront['routing'] = is_array($existingstorefront['routing'] ?? null)
            ? $existingstorefront['routing']
            : [];
        $existingstorefront['routing']['slugs'] = [
            'fr' => \local_subscriptions\url\CommerceProductSlugService::clean((string)($submitted['route_slug_fr'] ?? '')),
            'en' => \local_subscriptions\url\CommerceProductSlugService::clean((string)($submitted['route_slug_en'] ?? '')),
            'ru' => \local_subscriptions\url\CommerceProductSlugService::clean((string)($submitted['route_slug_ru'] ?? '')),
        ];

        $existingstorefront['locales'][$language]['seo'] = [
            'title' => trim((string)($submitted['seo_title'] ?? '')),
            'description' => $this->clean_seo_description(
                (string)($submitted['seo_description'] ?? '')
            ),
        ];
        // Keep FR/default compatibility for existing public pages and older installations.
        if ($language === 'fr') {
            $existingstorefront['sections'] = $sections;
        }
        $existingstorefront['recommendations'] = $this->parse_sku_lines((string)($submitted['recommendations'] ?? ''));

        $globalexperience = [
            'group' => $this->clean_group((string)($submitted['group'] ?? 'auto')),
            'trust' => $this->clean_trust((array)($submitted['trust'] ?? [])),
            'quickfacts' => $this->parse_items((string)($submitted['quickfacts'] ?? ''), 'value', 'label'),
        ];
        $existingstorefront['experience'] = $globalexperience;
        $existingstorefront['locales'][$language]['experience'] = [
            'quickfacts' => $globalexperience['quickfacts'],
        ];

        $existingstorefront['merchandising'] = [
            'featured' => !empty($submitted['featured']),
            'displayorder' => max(0, min(999999, (int)($submitted['displayorder'] ?? 1000))),
            'badges' => $this->clean_badges((array)($submitted['badges'] ?? [])),
            'promotions' => array_filter([
                'EUR' => $this->promotion_from_submission($submitted, 'eur'),
                'RUB' => $this->promotion_from_submission($submitted, 'rub'),
            ]),
        ];

        $showroom = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        $showroom['key'] = strtolower(trim((string)($submitted['showroom_key'] ?? '')));
        $showroom['discoverymode'] =
            \local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver::normalise_mode(
                (string)($submitted['showroom_discoverymode'] ?? 'storefront')
            );
        $showroom['showstorefrontcta'] = !empty($submitted['showroom_showstorefrontcta']);
        $showroom['mediaitemid'] = max(0, (int)($submitted['showroom_mediaitemid'] ?? 0));
        $showroom['locales'] = is_array($showroom['locales'] ?? null) ? $showroom['locales'] : [];
        $showroom['locales'][$language] = [
            'alt' => trim((string)($submitted['showroom_alt'] ?? '')),
        ];
        $metadata['showroom'] = $showroom;

        $metadata['storefront'] = $existingstorefront;
        unset($metadata['storefront_template'], $metadata['storefront_theme'], $metadata['storefront_sections']);
        return $metadata;
    }


    /** @return string[] */
    public static function groups(): array { return CommerceStorefrontExperienceResolver::GROUPS; }

    /** @return string[] */
    public static function trust_items(): array { return CommerceStorefrontExperienceResolver::TRUST_ITEMS; }

    /** @return string[] */
    public static function badges(): array {
        return CommerceStorefrontMerchandisingResolver::BADGES;
    }

    /** @param array<string,mixed> $submitted @return array<string,int|null>|null */
    private function promotion_from_submission(array $submitted, string $currency): ?array {
        $major = trim((string)($submitted['promotion_' . $currency . '_compare'] ?? ''));
        if ($major === '' || !is_numeric(str_replace(',', '.', $major))) {
            return null;
        }
        $minor = (int)round((float)str_replace(',', '.', $major) * 100);
        if ($minor <= 0) {
            return null;
        }

        return [
            'compareamountminor' => $minor,
            'start' => $this->submitted_date((string)($submitted['promotion_' . $currency . '_start'] ?? ''), false),
            'end' => $this->submitted_date((string)($submitted['promotion_' . $currency . '_end'] ?? ''), true),
        ];
    }

    /** @param string[] $badges @return string[] */
    private function clean_badges(array $badges): array {
        $clean = [];
        foreach ($badges as $badge) {
            $badge = strtolower(trim((string)$badge));
            if (in_array($badge, CommerceStorefrontMerchandisingResolver::BADGES, true)
                && !in_array($badge, $clean, true)) {
                $clean[] = $badge;
            }
        }
        return $clean;
    }

    /** @return string[] */
    private function parse_sku_lines(string $value): array {
        $result=[];
        foreach (preg_split('/\R+/', $value) ?: [] as $line) {
            $sku=strtoupper(trim($line));
            if ($sku !== '' && !in_array($sku,$result,true)) { $result[]=$sku; }
            if (count($result)>=4) { break; }
        }
        return $result;
    }

    private function clean_group(string $group): string {
        $group = strtolower(trim($group));
        return in_array($group, CommerceStorefrontExperienceResolver::GROUPS, true) ? $group : 'auto';
    }

    /** @param string[] $items @return string[] */
    private function clean_trust(array $items): array {
        $clean = [];
        foreach ($items as $item) {
            $item = strtolower(trim((string)$item));
            if (in_array($item, CommerceStorefrontExperienceResolver::TRUST_ITEMS, true)
                && !in_array($item, $clean, true)) {
                $clean[] = $item;
            }
        }
        return $clean;
    }

    /** @param array<int,array{value:string,label:string}> $facts */
    private function quick_facts_to_text(array $facts): string {
        return implode(PHP_EOL, array_map(
            static fn(array $fact): string => $fact['value'] . ' ||| ' . $fact['label'],
            $facts
        ));
    }

    private function minor_to_major(?int $minor): string {
        return $minor === null ? '' : number_format($minor / 100, 2, '.', '');
    }

    private function date_value(?int $timestamp): string {
        return $timestamp === null ? '' : userdate($timestamp, '%Y-%m-%d', 99, false);
    }

    private function submitted_date(string $value, bool $endofday): ?int {
        $value = trim($value);
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        $suffix = $endofday ? ' 23:59:59' : ' 00:00:00';
        $timestamp = strtotime($value . $suffix);
        return $timestamp === false ? null : $timestamp;
    }

    /** @return string[] */
    public static function templates(): array {
        return self::TEMPLATES;
    }

    /** @return string[] */
    public static function section_types(): array {
        return self::SECTION_TYPES;
    }

    /** @return array<int,array<string,string>> */
    private function parse_items(string $value, string $leftkey, string $rightkey): array {
        $items = [];
        foreach (preg_split('/\R/u', $value) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$left, $right] = array_pad(explode('|||', $line, 2), 2, '');
            $left = trim($left);
            if ($left !== '') {
                $items[] = [$leftkey => $left, $rightkey => trim($right)];
            }
        }
        return $items;
    }

    /** @param array<string,mixed> $section */
    private function section_content(string $type, array $section): string {
        return match ($type) {
            'hero', 'rich_text', 'image_text', 'h5p', 'instructor', 'cta',
            'features'
                => (string)($section['content'] ?? ''),
            'video', 'media' => (string)($section['caption'] ?? ''),
            'testimonial' => (string)($section['quote'] ?? ''),
            default => '',
        };
    }

    /** @param array<string,mixed> $section */
    private function section_auxiliary(string $type, array $section): string {
        return match ($type) {
            'hero', 'image_text', 'video', 'h5p', 'media', 'instructor'
                => (string)($section['url'] ?? ''),
            'testimonial' => (string)($section['author'] ?? ''),
            default => '',
        };
    }

    /** @param array<string,mixed> $section */
    private function section_items(string $type, array $section): string {
        $items = is_array($section['items'] ?? null) ? $section['items'] : [];
        $lines = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (in_array($type, ['features', 'program', 'timeline', 'comparison', 'accordion'], true)) {
                $left = trim((string)($item['title'] ?? ''));
                $right = trim((string)($item['content'] ?? ''));
            } else if ($type === 'testimonials') {
                $left = trim((string)($item['author'] ?? ''));
                $right = trim((string)($item['quote'] ?? ''));
            } else if ($type === 'gallery') {
                $left = trim((string)($item['url'] ?? ''));
                $right = trim((string)($item['alt'] ?? ''));
                $caption = trim((string)($item['caption'] ?? ''));
                if ($caption !== '') {
                    $right .= ' ||| ' . $caption;
                }
            } else if ($type === 'faq') {
                $left = trim((string)($item['question'] ?? ''));
                $right = trim((string)($item['answer'] ?? ''));
            } else {
                continue;
            }
            if ($left !== '') {
                $lines[] = $left . ' ||| ' . $right;
            }
        }
        return implode("\n", $lines);
    }


    /** @param string[] $allowed */
    private function choice(
        string $value,
        array $allowed,
        string $fallback
    ): string {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true)
            ? $value
            : $fallback;
    }

    private function clean_section_id(string $id, int $index): string {
        $id = strtolower(trim($id));
        if (preg_match('/^[a-z0-9_-]{1,64}$/', $id) !== 1) {
            return 'section-' . ($index + 1);
        }
        return $id;
    }

    private function clean_section_style(string $style): string {
        $style = strtolower(trim($style));
        return in_array(
            $style,
            \local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema::STYLES,
            true
        ) ? $style : 'default';
    }

    /** @return array<int,array<string,string>> */
    private function parse_gallery_items(string $value): array {
        $items = [];
        foreach (preg_split('/\R/u', $value) ?: [] as $line) {
            $parts = array_map(
                'trim',
                explode('|||', trim($line), 3)
            );
            if (($parts[0] ?? '') === '') {
                continue;
            }
            $items[] = [
                'url' => $parts[0],
                'alt' => $parts[1] ?? '',
                'caption' => $parts[2] ?? '',
            ];
        }
        return $items;
    }

    private function clean_seo_description(string $description): string {
        $description = trim(
            preg_replace('/\s+/u', ' ', strip_tags($description)) ?? ''
        );
        return \core_text::substr($description, 0, 320);
    }

    private function clean_theme(string $theme): string {
        $theme = strtolower(trim($theme));
        return preg_match('/^[a-z0-9_-]{1,32}$/', $theme) === 1 ? $theme : 'default';
    }
    /**
     * Keeps durable media references during a global save.
     *
     * File inputs are empty after a reload by design. Their absence must never
     * be interpreted as a request to detach an already persisted media area.
     *
     * @param array<int,array<string,mixed>> $submittedsections
     * @param array<int,array<string,mixed>> $existingsections
     * @return array<int,array<string,mixed>>
     */
    private function preserve_durable_section_data(
        array $submittedsections,
        array $existingsections
    ): array {
        $byid = [];
        foreach ($existingsections as $existing) {
            if (!is_array($existing)) {
                continue;
            }
            $id = trim((string)($existing['id'] ?? ''));
            if ($id !== '') {
                $byid[$id] = $existing;
            }
        }

        foreach ($submittedsections as &$section) {
            $id = trim((string)($section['id'] ?? ''));
            if ($id === '' || !isset($byid[$id])) {
                continue;
            }
            $existing = $byid[$id];
            if ((int)($section['mediaitemid'] ?? 0) <= 0
                && (int)($existing['mediaitemid'] ?? 0) > 0) {
                $section['mediaitemid'] = (int)$existing['mediaitemid'];
            }
            foreach (['imagemode', 'videosource'] as $key) {
                if (empty($section[$key]) && !empty($existing[$key])) {
                    $section[$key] = $existing[$key];
                }
            }
            if ((int)($section['h5pcontentid'] ?? 0) <= 0
                && (int)($existing['h5pcontentid'] ?? 0) > 0) {
                $section['h5pcontentid'] = (int)$existing['h5pcontentid'];
            }
            if (trim((string)($section['url'] ?? '')) === ''
                && trim((string)($existing['url'] ?? '')) !== '') {
                $section['url'] = $existing['url'];
            }
        }
        unset($section);

        return $submittedsections;
    }


}
