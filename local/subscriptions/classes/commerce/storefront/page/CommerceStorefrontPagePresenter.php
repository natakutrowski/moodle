<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontH5pService;
use local_subscriptions\commerce\showroom\CommerceShowroomMediaService;

/** Builds template-safe data while keeping all commercial actions common to every page. */
final class CommerceStorefrontPagePresenter {
    /** @return array<string, mixed> */
    public function present(
        CommerceStorefrontProduct $product,
        CommerceStorefrontPageDefinition $definition,
        string $currency,
        string $backurl
    ): array {
        $data = CommerceStorefrontPresenter::card($product, $currency);
        $coverurl = $this->product_visual_fallback($product);
        $data['coverurl'] = $coverurl;
        $data['hascover'] = $coverurl !== '';
        $data['description'] = format_text($product->get_description(), FORMAT_HTML);
        $data['backurl'] = $backurl;
        $data['backlabel'] = get_string('commerce_storefront_back', 'local_subscriptions');
        $data['theme'] = $definition->get_theme();
        $herofallbackcontent = $product->get_short_description() !== ''
            ? $product->get_short_description()
            : $product->get_description();
        $sections = array_map(
            fn(array $section): array => $this->present_section(
                $section,
                $coverurl,
                $product->get_name(),
                $herofallbackcontent
            ),
            $definition->get_sections()
        );
        // Composition presets deliberately create empty sections. They are useful
        // in the Builder, but must not render as blank coloured bands publicly.
        $data['sections'] = array_values(array_filter(
            $sections,
            static fn(array $section): bool => !empty($section['renderable'])
        ));
        $data['hassections'] = $data['sections'] !== [];
        $data['introsections'] = $data['sections'] === []
            ? []
            : [array_values($data['sections'])[0]];
        $data['remainingsections'] = $data['sections'] === []
            ? []
            : array_slice(array_values($data['sections']), 1);
        $data['hasremainingsections'] = $data['remainingsections'] !== [];
        $data['editorialschemaversion'] = $definition->get_schema_version();
        $data['layout'] = $definition->get_layout();
        $data['commerceposition'] = $definition->get_commerce_position();
        $productheadermode = $definition->get_product_header_mode();
        $hasbuilderhero = array_reduce(
            $definition->get_sections(),
            static fn(bool $carry, array $section): bool =>
                $carry || (($section['type'] ?? '') === 'hero'),
            false
        );
        $data['productheadermode'] = $productheadermode;
        $data['builderproducthero'] = $productheadermode === 'builder' && $hasbuilderhero;
        $data['showproducthero'] = $productheadermode === 'automatic'
            || ($productheadermode === 'builder' && !$hasbuilderhero);
        $data['commerceinhero'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::HERO_INTEGRATED
            && $data['showproducthero'];
        $data['commerceissidebar'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::SIDEBAR_STICKY;
        $data['commercebelowhero'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::BELOW_HERO
            || (
                $definition->get_commerce_position() === CommerceStorefrontLayoutContract::HERO_INTEGRATED
                && !$data['showproducthero']
            );
        $data['commerceafterintro'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::AFTER_INTRO;
        $data['commercepagebottom'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::PAGE_BOTTOM;
        $data['commercehidden'] = $definition->get_commerce_position()
            === CommerceStorefrontLayoutContract::NONE;
        $data['shellmode'] = $definition->get_shell_mode();
        $data['showheader'] = $definition->show_header();
        $data['showfooter'] = $definition->show_footer();
        $data['placeholderratio'] =
            CommerceStorefrontLayoutContract::placeholder_ratio(
                $definition->get_layout(),
                $product->get_type()
            );
        $data['placeholdericon'] =
            CommerceProductVisualAuditService::placeholder_icon(
                    $product->get_type()
                );
        $data['producttype'] = $product->get_type();

        return $data;
    }

    /** @param array<string, mixed> $section */
    private function present_section(
        array $section,
        string $heroFallbackUrl = '',
        string $heroFallbackTitle = '',
        string $heroFallbackContent = ''
    ): array {
        $type = (string)$section['type'];
        if ($type === 'hero') {
            if (trim((string)($section['title'] ?? '')) === '') {
                $section['title'] = $heroFallbackTitle;
            }
            if (trim((string)($section['content'] ?? '')) === '') {
                $section['content'] = $heroFallbackContent;
            }
        }
        $layout = CommerceStorefrontComposerLayout::normalise($section, 0);
        $base = [
            'type' => $type,
            'id' => (string)($section['id'] ?? ''),
            'style' => (string)($section['style'] ?? 'default'),
            'ishero' => $type === 'hero',
            'isrichtext' => $type === 'rich_text',
            'isimagetext' => $type === 'image_text',
            'isvideo' => $type === 'video',
            'ish5p' => $type === 'h5p',
            'isfeatures' => $type === 'features',
            'isprogram' => $type === 'program',
            'isinstructor' => $type === 'instructor',
            'ismedia' => $type === 'media',
            'istestimonial' => $type === 'testimonial',
            'istestimonials' => $type === 'testimonials',
            'isfaq' => $type === 'faq',
            'isgallery' => $type === 'gallery',
            'iscta' => $type === 'cta',
            'iscomponents' => $type === 'components',
            'istimeline' => $type === 'timeline',
            'iscomparison' => $type === 'comparison',
            'isaccordion' => $type === 'accordion',
            'presentation' => (string)($section['presentation'] ?? 'default'),
            'contentalignment' => in_array(
                (string)($section['contentalignment'] ?? 'left'),
                ['left', 'center', 'right'],
                true
            ) ? (string)($section['contentalignment'] ?? 'left') : 'left',
            'animation' => (string)($section['animation'] ?? 'none'),
            'hasanimation' => (string)($section['animation'] ?? 'none') !== 'none',
            'title' => format_string((string)($section['title'] ?? '')),
            'subtitle' => format_string((string)($section['subtitle'] ?? '')),
            'composerrowid' => $layout['rowid'],
            'composercolumn' => $layout['column'],
            'composercolumns' => $layout['columns'],
            'composerratio' => $layout['ratio'],
            'composerwidth' => $layout['width'],
            'composerbackground' => $layout['background'],
            'composerspacing' => $layout['spacing'],
            'composeralignment' => $layout['alignment'],
            'composerclasses' => implode(' ', [
                'commerce-product-section--composer',
                'commerce-product-section--columns-' . $layout['columns'],
                'commerce-product-section--column-' . $layout['column'],
                'commerce-product-section--ratio-' . $layout['ratio'],
                'commerce-product-section--width-' . $layout['width'],
                'commerce-product-section--background-' . $layout['background'],
                'commerce-product-section--spacing-' . $layout['spacing'],
                'commerce-product-section--align-' . $layout['alignment'],
                'commerce-product-section--content-' . (
                    in_array(
                        (string)($section['contentalignment'] ?? 'left'),
                        ['left', 'center', 'right'],
                        true
                    ) ? (string)($section['contentalignment'] ?? 'left') : 'left'
                ),
            ]),
        ];

        $presented = $base + match ($type) {
            'hero' => $this->present_hero($section, $heroFallbackUrl),
            'rich_text' => [
                'content' => $this->format_storefront_content(
                    (string)($section['content'] ?? ''),
                    (int)($section['mediaitemid'] ?? 0)
                ),
            ],
            'image_text' => $this->present_image_text($section),
            'video' => $this->present_video($section),
            'h5p' => $this->present_h5p($section),
            'features' => [
                'content' => $this->format_storefront_content(
                    (string)($section['content'] ?? ''),
                    (int)($section['mediaitemid'] ?? 0)
                ),
                'items' => $this->present_items($section['items'] ?? []),
                'featuresfour' => count($section['items'] ?? []) === 4,
            ],
            'program' => [
                'items' => $this->present_program($section['items'] ?? []),
            ],
            'instructor' => [
                'name' => format_string(
                    (string)($section['name'] ?? '')
                ),
                'role' => format_string(
                    (string)($section['role'] ?? '')
                ),
                'content' => format_text(
                    (string)($section['content'] ?? ''),
                    FORMAT_HTML
                ),
                'url' => $this->safe_media_url(
                    (string)($section['url'] ?? '')
                ),
                'alt' => format_string((string)($section['alt'] ?? '')),
            ],
            'media' => [
                'url' => $this->safe_media_url((string)($section['url'] ?? '')),
                'alt' => format_string((string)($section['alt'] ?? '')),
                'caption' => format_text((string)($section['caption'] ?? ''), FORMAT_HTML, ['para' => false]),
            ],
            'testimonial' => [
                'quote' => format_text((string)($section['quote'] ?? ''), FORMAT_HTML, ['para' => false]),
                'author' => format_string((string)($section['author'] ?? '')),
            ],
            'testimonials' => [
                'items' => $this->present_testimonials(
                    $section['items'] ?? []
                ),
            ],
            'faq' => [
                'items' => $this->present_faq($section['items'] ?? []),
            ],
            'gallery' => [
                'items' => $this->present_gallery(
                    $section['items'] ?? []
                ),
            ],
            'cta' => [
                'content' => $this->format_storefront_content(
                    (string)($section['content'] ?? ''),
                    (int)($section['mediaitemid'] ?? 0)
                ),
            ],
            'timeline', 'comparison', 'accordion' => [
                'items' => $this->present_items($section['items'] ?? []),
            ],
            'components' => [
                'items' => $this->present_components($section['items'] ?? []),
            ],
            default => [],
        };
        $presented['renderable'] = $this->is_renderable_section($presented);
        return $presented;
    }

    /** @param array<string,mixed> $section */
    private function is_renderable_section(array $section): bool {
        $type = (string)($section['type'] ?? '');
        if ($type === 'hero' || $type === 'cta') {
            return true;
        }

        if (trim((string)($section['title'] ?? '')) !== ''
            || trim((string)($section['subtitle'] ?? '')) !== '') {
            return true;
        }

        if (!empty($section['items'])) {
            return true;
        }

        foreach ([
            'content', 'imagehtml', 'videohtml', 'h5phtml', 'url',
            'quote', 'author', 'name', 'role', 'caption'
        ] as $key) {
            if (trim((string)($section[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @param array<string,mixed> $section */
    private function present_hero(array $section, string $fallbackurl): array {
        $files = CommerceStorefrontContentFileService::create();
        $layout = in_array(
            (string)($section['herolayout'] ?? 'text_media'),
            ['text_media', 'media_text', 'stacked', 'overlay'],
            true
        ) ? (string)($section['herolayout'] ?? 'text_media') : 'text_media';
        $ratio = in_array(
            (string)($section['heroratio'] ?? '55_45'),
            ['50_50', '55_45', '60_40', '45_55'],
            true
        ) ? (string)($section['heroratio'] ?? '55_45') : '55_45';
        $mediaratio = in_array(
            (string)($section['heromediaratio'] ?? 'original'),
            ['original', '1_1', '4_3', '16_9'],
            true
        ) ? (string)($section['heromediaratio'] ?? 'original') : 'original';
        $itemid = (int)($section['mediaitemid'] ?? 0);
        $uploaded = $files->get_slot_url($itemid, 'image');
        $external = $this->safe_media_url((string)($section['url'] ?? ''));
        $url = $uploaded !== null
            ? $uploaded->out(false)
            : (trim($external) !== '' ? $external : trim($fallbackurl));
        $alt = format_string((string)($section['alt'] ?? ''));

        return [
            'content' => $this->format_storefront_content(
                (string)($section['content'] ?? ''),
                $itemid,
                false
            ),
            'url' => $url,
            'hasurl' => $url !== '',
            'alt' => $alt,
            'mediaitemid' => $itemid,
            'usingfallbackmedia' => $uploaded === null && trim($external) === '' && $url !== '',
            'herolayout' => $layout,
            'heroratio' => $ratio,
            'heromediaratio' => $mediaratio,
            'heroclasses' => implode(' ', [
                'commerce-editorial-hero--layout-' . $layout,
                'commerce-editorial-hero--ratio-' . $ratio,
                'commerce-editorial-hero--media-' . $mediaratio,
            ]),
        ];
    }

    private function product_visual_fallback(CommerceStorefrontProduct $product): string {
        foreach (['product', 'storefront', 'showroom'] as $context) {
            $url = trim((string)($product->get_cover_url($context) ?? ''));
            if ($url !== '') {
                return $url;
            }
        }

        $showroom = CommerceShowroomMediaService::create()->definition(
            $product->get_metadata(),
            strtolower(explode('_', str_replace('-', '_', current_language()))[0])
        );
        return !empty($showroom['hasimage'])
            ? trim((string)$showroom['imageurl'])
            : '';
    }

    /** @param array<string,mixed> $section */
    private function present_image_text(array $section): array {
        $files = CommerceStorefrontContentFileService::create();
        $itemid = (int)($section['mediaitemid'] ?? 0);
        $uploaded = $files->get_slot_url($itemid, 'image');
        $url = $uploaded !== null
            ? $uploaded->out(false)
            : $this->safe_media_url(
                (string)($section['url'] ?? '')
            );
        $ratio = (string)($section['columnratio'] ?? '50_50');
        $alt = format_string((string)($section['alt'] ?? ''));
        $imagehtml = '';
        if (trim($url) !== '') {
            $imagehtml = \html_writer::empty_tag('img', [
                'src' => $url,
                'alt' => $alt,
                'loading' => 'lazy',
            ]);
        }
        return [
            'content' => $this->format_storefront_content(
                (string)($section['content'] ?? ''),
                $itemid
            ),
            'url' => $url,
            'hasurl' => trim($url) !== '',
            'imagehtml' => $imagehtml,
            'hasimagehtml' => $imagehtml !== '',
            'alt' => $alt,
            'imageleft' =>
                (($section['imageposition'] ?? 'left') === 'left'),
            'imagecontain' => (($section['imagefit'] ?? 'cover') === 'contain'),
            'imageright' =>
                (($section['imageposition'] ?? 'left') === 'right'),
            'ratio4060' => $ratio === '40_60',
            'ratio5050' => $ratio === '50_50',
            'ratio6040' => $ratio === '60_40',
        ];
    }

    /** @param array<string,mixed> $section */
    private function present_video(array $section): array {
        $files = CommerceStorefrontContentFileService::create();
        $itemid = (int)($section['mediaitemid'] ?? 0);
        $uploadedfile = $files->get_slot_file($itemid, 'video');
        $uploaded = $files->get_slot_url($itemid, 'video');
        $poster = $files->get_slot_url($itemid, 'poster');
        $source = (string)($section['videosource'] ?? 'upload');
        $external = trim((string)($section['url'] ?? ''));
        $embedurl = $this->external_video_embed_url(
            $source,
            $external
        );
        $videourl = $uploaded !== null
            ? $uploaded->out(false)
            : (
                $embedurl === null
                    ? $this->safe_media_url($external)
                    : ''
            );
        $ratio = (string)($section['videoratio'] ?? '16_9');

        $isfilevideo = $embedurl === null && trim($videourl) !== '';
        $posterurl = $poster !== null ? $poster->out(false) : '';
        $mimetype = $uploadedfile !== null
            ? trim((string)$uploadedfile->get_mimetype())
            : '';
        $videohtml = '';
        if ($isfilevideo) {
            $sourceattributes = [
                'src' => $videourl,
            ];
            if ($mimetype !== '') {
                $sourceattributes['type'] = $mimetype;
            }
            $videoattributes = [
                'controls' => 'controls',
                'preload' => 'metadata',
                'playsinline' => 'playsinline',
            ];
            if ($posterurl !== '') {
                $videoattributes['poster'] = $posterurl;
            }
            $videohtml = \html_writer::tag(
                'video',
                \html_writer::empty_tag('source', $sourceattributes),
                $videoattributes
            );
        }

        return [
            'url' => $videourl,
            'hasurl' => trim($videourl) !== '',
            'embedurl' => $embedurl ?? '',
            'isembed' => $embedurl !== null,
            'isfilevideo' => $isfilevideo,
            'videohtml' => $videohtml,
            'hasvideohtml' => $videohtml !== '',
            'posterurl' => $posterurl,
            'hasposter' => $posterurl !== '',
            'mimetype' => $mimetype,
            'hasmimetype' => $mimetype !== '',
            // Video sections use the common rich-content editor. Older
            // configurations may still expose a dedicated caption key, so
            // keep it as a fallback while preferring the persisted content.
            'caption' => $this->format_storefront_content(
                (string)($section['content'] ?? ($section['caption'] ?? '')),
                $itemid,
                false
            ),
            'ratio169' => $ratio === '16_9',
            'ratio43' => $ratio === '4_3',
            'ratio11' => $ratio === '1_1',
        ];
    }

    /** @param array<string,mixed> $section */
    private function present_h5p(array $section): array {
        $files = CommerceStorefrontContentFileService::create();
        $itemid = (int)($section['mediaitemid'] ?? 0);

        $uploadedpackage = $files->get_slot_url(
            $itemid,
            'h5p'
        );
        $embedurl = $uploadedpackage !== null
            ? (new \moodle_url('/h5p/embed.php', [
                'url' => $uploadedpackage->out(false),
            ]))->out(false)
            : null;

        if ($embedurl === null) {
            $embedurl = CommerceStorefrontH5pService::create()
                ->embed_url(
                    (int)($section['h5pcontentid'] ?? 0)
                );
        }

        if ($embedurl === null) {
            $candidate = $this->safe_media_url(
                (string)($section['url'] ?? '')
            );
            $embedurl = trim($candidate) !== ''
                ? $candidate
                : null;
        }
        $height = max(
            240,
            min(1200, (int)($section['h5pheight'] ?? 640))
        );
        $h5phtml = '';
        if ($embedurl !== null && trim($embedurl) !== '') {
            $h5phtml = \html_writer::tag('iframe', '', [
                'src' => $embedurl,
                'title' => format_string((string)($section['title'] ?? 'H5P')),
                'loading' => 'lazy',
                'style' => 'min-height: ' . $height . 'px',
                'allowfullscreen' => 'allowfullscreen',
            ]);
        }
        return [
            'embedurl' => $embedurl ?? '',
            'hasembed' => $embedurl !== null,
            'h5phtml' => $h5phtml,
            'hash5phtml' => $h5phtml !== '',
            'height' => $height,
            'content' => $this->format_storefront_content(
                (string)($section['content'] ?? ''),
                $itemid,
                false
            ),
        ];
    }

    private function format_storefront_content(
        string $content,
        int $itemid,
        bool $paragraphs = true
    ): string {
        $files = CommerceStorefrontContentFileService::create();
        $content = $files->rewrite_for_display($content, $itemid);
        return format_text($content, FORMAT_HTML, [
            'context' => $files->context(),
            'para' => $paragraphs,
            'filter' => true,
            'noclean' => true,
        ]);
    }

    private function external_video_embed_url(
        string $source,
        string $url
    ): ?string {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if ($source === 'youtube') {
            if (
                preg_match(
                    '~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/))'
                        . '([A-Za-z0-9_-]{6,})~',
                    $url,
                    $matches
                ) === 1
            ) {
                return 'https://www.youtube-nocookie.com/embed/'
                    . rawurlencode($matches[1]);
            }
            return null;
        }

        if ($source === 'vimeo') {
            if (
                preg_match(
                    '~vimeo\\.com/(?:video/)?(\\d+)~',
                    $url,
                    $matches
                ) === 1
            ) {
                return 'https://player.vimeo.com/video/'
                    . rawurlencode($matches[1]);
            }
            return null;
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function present_items(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $result[] = ['title' => format_string($item), 'content' => ''];
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $result[] = [
                'title' => format_string((string)($item['title'] ?? '')),
                'content' => format_text((string)($item['content'] ?? ''), FORMAT_HTML, ['para' => false]),
            ];
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function present_faq(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = trim((string)($item['question'] ?? ''));
            if ($question === '') {
                continue;
            }
            $result[] = [
                'question' => format_string($question),
                'answer' => format_text((string)($item['answer'] ?? ''), FORMAT_HTML),
            ];
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function present_components(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = (string)($item['name'] ?? $item['sku'] ?? $item['component_sku'] ?? '');
            if (trim($label) === '') {
                continue;
            }
            $result[] = [
                'name' => format_string($label),
                'description' => format_text((string)($item['description'] ?? ''), FORMAT_HTML, ['para' => false]),
            ];
        }
        return $result;
    }


    /** @return array<int,array<string,mixed>> */
    private function present_program(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string)($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $result[] = [
                'number' => $index + 1,
                'title' => format_string($title),
                'content' => format_text(
                    (string)($item['content'] ?? ''),
                    FORMAT_HTML,
                    ['para' => false]
                ),
            ];
        }

        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    private function present_testimonials(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $quote = trim((string)($item['quote'] ?? ''));
            if ($quote === '') {
                continue;
            }
            $result[] = [
                'quote' => format_text(
                    $quote,
                    FORMAT_HTML,
                    ['para' => false]
                ),
                'author' => format_string(
                    (string)($item['author'] ?? '')
                ),
            ];
        }

        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    private function present_gallery(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = $this->safe_media_url(
                (string)($item['url'] ?? '')
            );
            if ($url === '') {
                continue;
            }
            $result[] = [
                'url' => $url,
                'alt' => format_string(
                    (string)($item['alt'] ?? '')
                ),
                'caption' => format_string(
                    (string)($item['caption'] ?? '')
                ),
            ];
        }

        return $result;
    }

    private function safe_media_url(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        try {
            $moodleurl = new \moodle_url($url);
            return $moodleurl->out(false);
        } catch (\Throwable) {
            return '';
        }
    }
}
