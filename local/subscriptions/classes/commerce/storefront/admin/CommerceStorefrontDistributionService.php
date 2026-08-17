<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;
use local_subscriptions\commerce\storefront\merchandising\CommerceStorefrontMerchandisingResolver;
use local_subscriptions\url\CommerceProductSlugService;

/**
 * Persists Page Boutique discovery/merchandising metadata without touching
 * editorial sections, layout, assets, pricing or access rules.
 */
final class CommerceStorefrontDistributionService {
    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $submitted
     * @return array<string,mixed>
     */
    public function apply(
        array $metadata,
        string $language,
        array $submitted
    ): array {
        $language = $this->language($language);
        $storefront = is_array($metadata['storefront'] ?? null)
            ? $metadata['storefront']
            : [];
        $storefront['locales'] = is_array(
            $storefront['locales'] ?? null
        ) ? $storefront['locales'] : [];
        $storefront['locales'][$language] = is_array(
            $storefront['locales'][$language] ?? null
        ) ? $storefront['locales'][$language] : [];

        $storefront['merchandising'] = array_merge(
            is_array($storefront['merchandising'] ?? null)
                ? $storefront['merchandising']
                : [],
            [
                'featured' => !empty($submitted['featured']),
                'displayorder' => max(
                    0,
                    min(
                        999999,
                        (int)($submitted['displayorder'] ?? 1000)
                    )
                ),
                'badges' => $this->badges(
                    (array)($submitted['badges'] ?? [])
                ),
            ]
        );

        $experience = is_array($storefront['experience'] ?? null)
            ? $storefront['experience']
            : [];
        $experience['group'] = $this->group(
            (string)($submitted['group'] ?? 'auto')
        );

        $trustmode = strtolower(trim((string)(
            $submitted['trustmode'] ?? 'auto'
        )));
        if ($trustmode === 'custom') {
            $experience['trust'] = $this->trust(
                (array)($submitted['trust'] ?? [])
            );
        } else {
            // Absence is meaningful: the runtime resolver applies its
            // product-type defaults.
            unset($experience['trust']);
        }

        $quickfacts = $this->items(
            (string)($submitted['quickfacts'] ?? '')
        );
        if ($language === 'fr') {
            $experience['quickfacts'] = $quickfacts;
        }
        $storefront['experience'] = $experience;

        $localexperience = is_array(
            $storefront['locales'][$language]['experience'] ?? null
        ) ? $storefront['locales'][$language]['experience'] : [];
        $localexperience['quickfacts'] = $quickfacts;
        $storefront['locales'][$language]['experience'] =
            $localexperience;

        $storefront['recommendations'] = $this->recommendations(
            (array)($submitted['recommendations'] ?? [])
        );

        $seomode = strtolower(trim((string)(
            $submitted['seomode'] ?? 'auto'
        )));
        if ($seomode === 'custom') {
            $storefront['locales'][$language]['seo'] = [
                'title' => trim((string)($submitted['seotitle'] ?? '')),
                'description' => $this->seo_description(
                    (string)($submitted['seodescription'] ?? '')
                ),
            ];
        } else {
            unset($storefront['locales'][$language]['seo']);
        }

        $storefront['routing'] = is_array(
            $storefront['routing'] ?? null
        ) ? $storefront['routing'] : [];
        $storefront['routing']['slugs'] = [
            'fr' => CommerceProductSlugService::clean(
                (string)($submitted['slugfr'] ?? '')
            ),
            'en' => CommerceProductSlugService::clean(
                (string)($submitted['slugen'] ?? '')
            ),
            'ru' => CommerceProductSlugService::clean(
                (string)($submitted['slugru'] ?? '')
            ),
        ];

        $metadata['storefront'] = $storefront;

        $showroom = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        $showroom['key'] = strtolower(trim((string)(
            $submitted['showroomkey'] ?? ''
        )));
        $showroom['discoverymode'] =
            CommerceProductDiscoveryUrlResolver::normalise_mode(
                (string)($submitted['discoverymode'] ?? 'storefront')
            );
        $showroom['showstorefrontcta'] =
            !empty($submitted['showstorefrontcta']);

        // Deliberately preserve mediaitemid/localised alt and any future
        // Showroom keys managed by Media & Files or another specialised UI.
        $metadata['showroom'] = $showroom;

        return $metadata;
    }

    /** @param string[] $values @return string[] */
    private function badges(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $value = strtolower(trim((string)$value));
            if (
                in_array(
                    $value,
                    CommerceStorefrontMerchandisingResolver::BADGES,
                    true
                )
                && !in_array($value, $result, true)
            ) {
                $result[] = $value;
            }
        }
        return $result;
    }

    private function group(string $value): string {
        $value = strtolower(trim($value));
        return in_array(
            $value,
            CommerceStorefrontExperienceResolver::GROUPS,
            true
        ) ? $value : 'auto';
    }

    /** @param string[] $values @return string[] */
    private function trust(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $value = strtolower(trim((string)$value));
            if (
                in_array(
                    $value,
                    CommerceStorefrontExperienceResolver::TRUST_ITEMS,
                    true
                )
                && !in_array($value, $result, true)
            ) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /** @return array<int,array{value:string,label:string}> */
    private function items(string $text): array {
        $result = [];
        foreach (preg_split('/\R+/', $text) ?: [] as $line) {
            $parts = array_map('trim', explode('|||', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }
            $result[] = [
                'value' => \core_text::substr($parts[0], 0, 40),
                'label' => \core_text::substr($parts[1], 0, 80),
            ];
            if (count($result) >= 6) {
                break;
            }
        }
        return $result;
    }

    /** @param string[] $values @return string[] */
    private function recommendations(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $sku = strtoupper(trim((string)$value));
            if ($sku !== '' && !in_array($sku, $result, true)) {
                $result[] = $sku;
            }
            if (count($result) >= 4) {
                break;
            }
        }
        return $result;
    }

    private function seo_description(string $value): string {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        return \core_text::substr($value, 0, 320);
    }

    private function language(string $language): string {
        $language = strtolower(trim($language ?: current_language()));
        return explode(
            '_',
            str_replace('-', '_', $language)
        )[0] ?: 'fr';
    }
}
