<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\localisation;

defined('MOODLE_INTERNAL') || die();

/**
 * Copies one Storefront locale into another without touching global commerce configuration.
 */
final class CommerceStorefrontLocaleTransferService {
    public const LANGUAGES = ['fr', 'en', 'ru'];

    /**
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function copy(array $metadata, string $source, string $target): array {
        $source = $this->normalise_language($source);
        $target = $this->normalise_language($target);

        if ($source === $target) {
            throw new \invalid_parameter_exception('Source and target Storefront locales must be different.');
        }

        $storefront = $metadata['storefront'] ?? [];
        if (is_string($storefront)) {
            $decoded = json_decode($storefront, true);
            $storefront = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($storefront)) {
            $storefront = [];
        }

        $storefront['locales'] = is_array($storefront['locales'] ?? null)
            ? $storefront['locales']
            : [];
        $sourcelocale = $storefront['locales'][$source] ?? null;
        if ((!is_array($sourcelocale) || $sourcelocale === []) && $source === 'fr') {
            $sourcelocale = array_filter([
                'sections' => is_array($storefront['sections'] ?? null)
                    ? $storefront['sections']
                    : [],
                'seo' => is_array($storefront['seo'] ?? null)
                    ? $storefront['seo']
                    : [],
                'experience' => is_array($storefront['experience'] ?? null)
                    ? ['quickfacts' => (array)($storefront['experience']['quickfacts'] ?? [])]
                    : [],
            ], static fn(array $value): bool => $value !== []);
        }
        if (!is_array($sourcelocale) || $sourcelocale === []) {
            throw new \moodle_exception(
                'commerce_storefront_locale_source_empty',
                'local_subscriptions'
            );
        }

        $storefront['locales'][$target] = $this->deep_copy($sourcelocale);

        // FR is also the historical/default compatibility locale on public Storefront pages.
        if ($target === 'fr' && isset($storefront['locales'][$target]['sections'])) {
            $storefront['sections'] = $this->deep_copy(
                (array)$storefront['locales'][$target]['sections']
            );
        }

        $metadata['storefront'] = $storefront;

        $showroom = is_array($metadata['showroom'] ?? null)
            ? $metadata['showroom']
            : [];
        $showroom['locales'] = is_array($showroom['locales'] ?? null)
            ? $showroom['locales']
            : [];
        if (is_array($showroom['locales'][$source] ?? null)) {
            $showroom['locales'][$target] = $this->deep_copy(
                $showroom['locales'][$source]
            );
            $metadata['showroom'] = $showroom;
        }

        return $metadata;
    }

    public function normalise_language(string $language): string {
        $language = strtolower(trim($language));
        $language = explode('_', str_replace('-', '_', $language))[0];
        if (!in_array($language, self::LANGUAGES, true)) {
            throw new \invalid_parameter_exception('Unsupported Storefront locale.');
        }
        return $language;
    }

    /** @return array<string,mixed> */
    private function deep_copy(array $value): array {
        $json = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        /** @var array<string,mixed> $copy */
        $copy = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return $copy;
    }
}
