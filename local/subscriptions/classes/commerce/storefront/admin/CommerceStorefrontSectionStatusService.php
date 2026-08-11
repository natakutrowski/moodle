<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;

/**
 * Computes editorial readiness of one Storefront section.
 *
 * "ready" means the section satisfies the expected contract for its semantic
 * type/presentation. "attention" means it already contains meaningful content
 * and can render, but one recommended element is missing. "empty" means there
 * is not enough editorial content to consider the section configured.
 */
final class CommerceStorefrontSectionStatusService {
    public const READY = 'ready';
    public const ATTENTION = 'attention';
    public const EMPTY = 'empty';

    public function __construct(
        private readonly CommerceStorefrontContentFileService $files
    ) {
    }

    /** @param array<string,mixed> $section */
    public function status(array $section): string {
        $type = strtolower(trim((string)($section['type'] ?? '')));
        $presentation = strtolower(trim((string)(
            $section['presentation'] ?? 'default'
        )));
        $itemid = max(0, (int)($section['mediaitemid'] ?? 0));
        $rawcontent = (string)(
            $section['content']
            ?? $section['caption']
            ?? $section['quote']
            ?? ''
        );
        $content = trim(strip_tags($rawcontent));
        $title = trim((string)(
            $section['title']
            ?? $section['name']
            ?? ''
        ));
        $subtitle = trim((string)($section['subtitle'] ?? ''));
        $url = trim((string)($section['url'] ?? ''));
        $items = is_array($section['items'] ?? null)
            ? $section['items']
            : [];

        $hasimage = $this->has_slot($itemid, 'image') || $url !== '';
        $hasvideo = $this->has_slot($itemid, 'video') || $url !== '';
        $hash5p = $this->has_slot($itemid, 'h5p')
            || (int)($section['h5pcontentid'] ?? 0) > 0
            || $url !== '';
        $hastext = $title !== '' || $subtitle !== '' || $content !== '';
        $hasitems = $items !== [];

        // Semantic statement/transition intentionally supports title-only
        // editorial sections regardless of their historical section type.
        if ($presentation === 'statement') {
            return $hastext ? self::READY : self::EMPTY;
        }

        // CTA Commerce is valid with editorial copy OR the semantic commerce
        // presentation because Native pricing/actions provide the core content.
        if ($type === 'cta' && $presentation === 'commerce') {
            return self::READY;
        }

        return match ($type) {
            'image_text' => $this->status_pair($hastext, $hasimage),
            'video' => $hasvideo
                ? self::READY
                : ($hastext || str_contains($rawcontent, '@@PLUGINFILE@@')
                    ? self::ATTENTION
                    : self::EMPTY),
            'h5p' => $hash5p
                ? self::READY
                : ($hastext ? self::ATTENTION : self::EMPTY),
            'rich_text' => $content !== '' ? self::READY : self::EMPTY,
            'gallery', 'features', 'program', 'testimonials', 'faq',
            'timeline', 'comparison', 'accordion' => $hasitems
                ? self::READY
                : ($hastext ? self::ATTENTION : self::EMPTY),
            'hero' => ($hastext || $hasimage)
                ? self::READY
                : self::EMPTY,
            'cta', 'instructor', 'testimonial', 'media' => $hastext || $url !== ''
                ? self::READY
                : self::EMPTY,
            default => $hastext || $hasitems ? self::READY : self::EMPTY,
        };
    }

    /** @param array<string,mixed> $section */
    public function is_ready(array $section): bool {
        return $this->status($section) === self::READY;
    }

    private function status_pair(bool $hastext, bool $hasmedia): string {
        if ($hastext && $hasmedia) {
            return self::READY;
        }
        if ($hastext || $hasmedia) {
            return self::ATTENTION;
        }
        return self::EMPTY;
    }

    private function has_slot(int $itemid, string $slot): bool {
        return $itemid > 0
            && $this->files->get_slot_file($itemid, $slot) !== null;
    }
}
