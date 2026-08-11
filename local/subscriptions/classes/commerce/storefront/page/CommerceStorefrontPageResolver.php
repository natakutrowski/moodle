<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\readmodel\CommerceStorefrontProduct;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleResolver;

/** Resolves a safe, product-specific editorial composition from catalogue metadata. */
final class CommerceStorefrontPageResolver {
    private const SECTION_TYPES = CommerceStorefrontSectionSchema::TYPES;

    public function resolve(CommerceStorefrontProduct $product): CommerceStorefrontPageDefinition {
        $configuration = $this->configuration($product->get_metadata());
        $layout = CommerceStorefrontLayoutContract::normalise_layout(
            (string)($configuration['layout']
                ?? $configuration['template']
                ?? 'standard')
        );

        // The admin-facing page model deliberately exposes only the generic
        // visual modes (Standard / Editorial / Immersive). When Standard is
        // selected for a native course-access product, resolve it to the
        // dedicated Course storefront layout. This keeps the Builder simple
        // while making the product-specific Course template reachable.
        if ($layout === CommerceStorefrontLayoutContract::STANDARD
                && $product->get_type() === 'course_access') {
            $layout = CommerceStorefrontLayoutContract::COURSE;
        }
        if ($layout === CommerceStorefrontLayoutContract::STANDARD
                && $product->get_type() === 'bundle') {
            $layout = CommerceStorefrontLayoutContract::BUNDLE;
        }
        $template = CommerceStorefrontLayoutContract::template($layout);
        $commerceposition =
            CommerceStorefrontLayoutContract::normalise_commerce_position(
                (string)($configuration['commerce_position'] ?? ''),
                $layout
            );
        $theme = $this->clean_theme((string)($configuration['theme'] ?? 'default'));
        $sections = $this->normalise_sections($configuration['sections'] ?? []);

        if ($sections === [] && $layout !== CommerceStorefrontLayoutContract::BUNDLE) {
            $sections = $this->default_sections($product);
        }

        // The dedicated Bundle template renders its catalogue components in a
        // compact, product-aware block. Do not render a second generic
        // "components" section below the hero.
        if ($layout === CommerceStorefrontLayoutContract::BUNDLE) {
            $sections = array_values(array_filter(
                $sections,
                static fn(array $section): bool => ($section['type'] ?? '') !== 'components'
            ));
        }

        foreach ($sections as &$section) {
            if (($section['type'] ?? '') === 'components' && empty($section['items'])) {
                $section['items'] = $product->get_components();
            }
        }
        unset($section);

        $shellmode = strtolower(trim((string)($configuration['shell_mode'] ?? 'standard')));
        if (!in_array($shellmode, ['standard', 'fullwidth', 'landing', 'immersive'], true)) {
            $shellmode = 'standard';
        }
        $showheader = !array_key_exists('show_header', $configuration)
            || (bool)$configuration['show_header'];
        $showfooter = !array_key_exists('show_footer', $configuration)
            || (bool)$configuration['show_footer'];
        $productheadermode = strtolower(trim((string)($configuration['product_header_mode'] ?? 'automatic')));
        if (!in_array($productheadermode, ['automatic', 'builder', 'hidden'], true)) {
            $productheadermode = 'automatic';
        }

        return new CommerceStorefrontPageDefinition(
            $template,
            $sections,
            $theme,
            CommerceStorefrontSectionSchema::VERSION,
            $layout,
            $commerceposition,
            $shellmode,
            $showheader,
            $showfooter,
            $productheadermode
        );
    }

    /** @return array<string, mixed> */
    private function configuration(array $metadata): array {
        $configuration = $metadata['storefront'] ?? null;
        if (is_string($configuration)) {
            $decoded = json_decode($configuration, true);
            $configuration = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($configuration)) {
            $configuration = [];
        }

        foreach (['layout', 'commerce_position', 'shell_mode', 'show_header', 'show_footer', 'product_header_mode', 'template', 'theme', 'sections'] as $key) {
            $legacykey = 'storefront_' . $key;
            if (!array_key_exists($key, $configuration) && array_key_exists($legacykey, $metadata)) {
                $configuration[$key] = $metadata[$legacykey];
            }
        }

        if (is_string($configuration['sections'] ?? null)) {
            $decoded = json_decode((string)$configuration['sections'], true);
            $configuration['sections'] = is_array($decoded) ? $decoded : [];
        }

        return (new CommerceStorefrontLocaleResolver())->resolve($configuration);
    }

    /**
     * @param mixed $sections
     * @return array<int, array<string, mixed>>
     */
    private function normalise_sections(mixed $sections): array {
        if (!is_array($sections)) {
            return [];
        }

        return CommerceStorefrontSectionSchema::sort_visible($sections);
    }

    /** @return array<int, array<string, mixed>> */
    private function default_sections(CommerceStorefrontProduct $product): array {
        $sections = [];

        if (trim($product->get_description()) !== '') {
            $sections[] = [
                'type' => 'rich_text',
                'content' => $product->get_description(),
            ];
        }

        if ($product->get_components() !== []) {
            $sections[] = [
                'type' => 'components',
                'items' => $product->get_components(),
            ];
        }

        return $sections;
    }

    private function clean_theme(string $theme): string {
        $theme = strtolower(trim($theme));
        return preg_match('/^[a-z0-9_-]{1,32}$/', $theme) === 1 ? $theme : 'default';
    }
}
