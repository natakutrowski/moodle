<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** Prepares SEO metadata and structured data for bespoke Showroom pages. */
final class CommerceShowroomSeoService {
    /**
     * @param array<int,array<string,mixed>> $offers
     * @return array<string,mixed>
     */
    public function present(
        CommerceShowroomDefinition $definition,
        array $offers = []
    ): array {
        $canonical = CommerceShowroomUrl::make($definition)->out(false);
        $image = trim($definition->get_social_image());
        if ($image === '') {
            $image = $this->preferred_image($offers);
        }
        $alternates = [];

        foreach ($definition->get_slugs() as $language => $slug) {
            if ($slug === '') {
                continue;
            }
            $alternates[] = [
                'language' => $language,
                'url' => CommerceShowroomUrl::make(
                    $definition,
                    [],
                    $language
                )->out(false),
            ];
        }

        $language = $this->language();
        $configured = $definition->get_seo($language);
        $title = $this->configured_or_legacy(
            (string)($configured['title'] ?? ''),
            $definition->get_title_key()
        );
        $description = $this->configured_or_legacy(
            (string)($configured['description'] ?? ''),
            $definition->get_description_key()
        );
        $socialtitle = trim((string)($configured['socialtitle'] ?? '')) ?: $title;
        $socialdescription = trim((string)($configured['socialdescription'] ?? '')) ?: $description;

        return [
            'title' => $title,
            'description' => $description,
            'socialtitle' => $socialtitle,
            'socialdescription' => $socialdescription,
            'keywords' => trim((string)($configured['keywords'] ?? '')),
            'canonical' => $canonical,
            'image' => $image,
            'hasimage' => $image !== '',
            'alternates' => $alternates,
            'jsonld' => $this->json_ld(
                $definition,
                $offers,
                $canonical,
                $image,
                $title,
                $description
            ),
        ];
    }

    /** @param array<string,mixed> $seo */
    public function head_html(array $seo): string {
        $lines = [
            '<meta name="description" content="' .
                s((string)$seo['description']) . '">',
            '<meta name="robots" content="index,follow,max-image-preview:large">',
            '<link rel="canonical" href="' .
                s((string)$seo['canonical']) . '">',
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="CampusFR">',
            '<meta property="og:title" content="' .
                s((string)$seo['socialtitle']) . '">',
            '<meta property="og:description" content="' .
                s((string)$seo['socialdescription']) . '">',
            '<meta property="og:url" content="' .
                s((string)$seo['canonical']) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' .
                s((string)$seo['socialtitle']) . '">',
            '<meta name="twitter:description" content="' .
                s((string)$seo['socialdescription']) . '">',
        ];

        if (trim((string)($seo['keywords'] ?? '')) !== '') {
            $lines[] = '<meta name="keywords" content="' .
                s((string)$seo['keywords']) . '">';
        }

        if (!empty($seo['hasimage'])) {
            $image = s((string)$seo['image']);
            $lines[] = '<meta property="og:image" content="' . $image . '">';
            $lines[] = '<meta name="twitter:image" content="' . $image . '">';
        }

        foreach ((array)($seo['alternates'] ?? []) as $alternate) {
            if (!is_array($alternate)) {
                continue;
            }
            $language = s((string)($alternate['language'] ?? ''));
            $url = s((string)($alternate['url'] ?? ''));
            if ($language === '' || $url === '') {
                continue;
            }
            $lines[] = '<link rel="alternate" hreflang="' .
                $language . '" href="' . $url . '">';
        }

        $lines[] = '<link rel="alternate" hreflang="x-default" href="' .
            s((string)$seo['canonical']) . '">';

        if (!empty($seo['jsonld'])) {
            $lines[] = '<script type="application/ld+json">' .
                (string)$seo['jsonld'] .
                '</script>';
        }

        return implode("\n", $lines);
    }

    private function configured_or_legacy(string $configured, string $legacykey): string {
        $configured = trim($configured);
        if ($configured !== '') {
            return $configured;
        }

        $legacykey = trim($legacykey);
        if ($legacykey === '') {
            return 'CampusFR';
        }

        return get_string($legacykey, 'local_subscriptions');
    }

    private function language(): string {
        $language = strtolower((string)current_language());
        $language = explode('_', str_replace('-', '_', $language))[0];
        return in_array($language, ['fr', 'en', 'ru'], true) ? $language : 'fr';
    }

    /** @param array<int,array<string,mixed>> $offers */
    private function preferred_image(array $offers): string {
        foreach (['bundle', 'course', 'pdf'] as $preferredrole) {
            foreach ($offers as $offer) {
                if (
                    (string)($offer['role'] ?? '') === $preferredrole &&
                    !empty($offer['hascover']) &&
                    trim((string)($offer['coverurl'] ?? '')) !== ''
                ) {
                    return (string)$offer['coverurl'];
                }
            }
        }

        return '';
    }

    /**
     * @param array<int,array<string,mixed>> $offers
     */
    private function json_ld(
        CommerceShowroomDefinition $definition,
        array $offers,
        string $canonical,
        string $image,
        string $title,
        string $description
    ): string {
        $offeritems = [];

        foreach ($offers as $offer) {
            if (
                empty($offer['available']) ||
                empty($offer['hasprice']) ||
                trim((string)($offer['priceformatted'] ?? '')) === ''
            ) {
                continue;
            }

            $numericprice = $this->numeric_price(
                (string)$offer['priceformatted']
            );
            if ($numericprice === null) {
                continue;
            }

            $offeritems[] = [
                '@type' => 'Offer',
                'name' => (string)($offer['name'] ?? ''),
                'price' => $numericprice,
                'priceCurrency' => strtoupper(
                    (string)($offer['currency'] ?? '')
                ),
                'availability' => 'https://schema.org/InStock',
                'url' => (string)($offer['detailsurl'] ?? $canonical),
            ];
        }

        $product = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'CampusFR',
            ],
        ];

        if ($image !== '') {
            $product['image'] = [$image];
        }
        if ($offeritems !== []) {
            $product['offers'] = $offeritems;
        }

        return json_encode(
            $product,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
        ) ?: '';
    }

    private function numeric_price(string $formatted): ?string {
        $value = preg_replace('/[^\d,.\-]/u', '', $formatted) ?? '';
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (
            str_contains($value, ',') &&
            str_contains($value, '.')
        ) {
            $lastcomma = strrpos($value, ',');
            $lastdot = strrpos($value, '.');
            $decimal = $lastcomma > $lastdot ? ',' : '.';
            $thousand = $decimal === ',' ? '.' : ',';
            $value = str_replace($thousand, '', $value);
            $value = str_replace($decimal, '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value)
            ? number_format((float)$value, 2, '.', '')
            : null;
    }
}
