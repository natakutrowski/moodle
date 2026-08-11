<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\seo;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;

/** Localised, controlled SEO projection for one product page. */
final class CommerceStorefrontSeoPresenter {
    /** @return array<string,string> */
    public function present(
        CommerceStorefrontProduct $product,
        string $canonicalurl,
        ?string $language = null
    ): array {
        $language = $this->language($language ?: current_language());
        $configuration = $product->get_metadata()['storefront'] ?? [];
        if (is_string($configuration)) {
            $decoded = json_decode($configuration, true);
            $configuration = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($configuration)) {
            $configuration = [];
        }

        $localized = is_array(
            $configuration['locales'][$language] ?? null
        ) ? $configuration['locales'][$language] : [];
        $seo = is_array($localized['seo'] ?? null)
            ? $localized['seo']
            : (
                is_array($configuration['seo'] ?? null)
                    ? $configuration['seo']
                    : []
            );

        $title = trim((string)($seo['title'] ?? ''));
        if ($title === '') {
            $title = $product->get_name();
        }

        $description = trim((string)($seo['description'] ?? ''));
        if ($description === '') {
            $description = $product->get_short_description();
        }
        if ($description === '') {
            $description = strip_tags($product->get_description());
        }
        $description = $this->compact($description, 160);

        $image = trim((string)$product->get_cover_url('social'));
        if ($image === '') {
            $image = trim((string)$product->get_cover_url('product'));
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => trim($canonicalurl),
            'image' => $image,
            'locale' => str_replace('_', '-', current_language()),
            'type' => 'product',
        ];
    }

    /** @param array<string,string> $seo */
    public function head_html(array $seo): string {
        $tags = [];
        if ($seo['canonical'] !== '') {
            $tags[] = '<link rel="canonical" href="'
                . s($seo['canonical']) . '">';
        }

        foreach ([
            'description' => $seo['description'],
            'og:title' => $seo['title'],
            'og:description' => $seo['description'],
            'og:type' => $seo['type'],
            'og:url' => $seo['canonical'],
            'og:image' => $seo['image'],
            'og:locale' => $seo['locale'],
            'twitter:card' => $seo['image'] !== ''
                ? 'summary_large_image'
                : 'summary',
            'twitter:title' => $seo['title'],
            'twitter:description' => $seo['description'],
            'twitter:image' => $seo['image'],
        ] as $name => $content) {
            if ($content === '') {
                continue;
            }
            $attribute = $name === 'description' || str_starts_with(
                $name,
                'twitter:'
            ) ? 'name' : 'property';
            $tags[] = '<meta '
                . $attribute . '="' . s($name)
                . '" content="' . s($content) . '">';
        }

        return implode("\n", $tags);
    }

    private function language(string $language): string {
        return explode(
            '_',
            str_replace('-', '_', strtolower(trim($language)))
        )[0];
    }

    private function compact(string $value, int $maxlength): string {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if (\core_text::strlen($value) <= $maxlength) {
            return $value;
        }

        return rtrim(
            \core_text::substr($value, 0, $maxlength - 1)
        ) . '…';
    }
}
