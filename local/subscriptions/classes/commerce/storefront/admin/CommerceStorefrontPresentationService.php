<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;

/**
 * Owns the presentation-only slice of Storefront metadata.
 *
 * It intentionally never modifies editorial sections, distribution, SEO,
 * Showroom configuration, pricing or access.
 */
final class CommerceStorefrontPresentationService {
    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed> $submitted
     * @return array<string,mixed>
     */
    public function apply(array $metadata, array $submitted): array {
        $storefront = is_array($metadata['storefront'] ?? null)
            ? $metadata['storefront']
            : [];

        $template = strtolower(trim((string)($submitted['template'] ?? 'default')));
        if (!in_array($template, CommerceStorefrontPageEditor::templates(), true)) {
            $template = 'default';
        }

        $position = strtolower(trim((string)($submitted['commerceposition'] ?? 'sidebar_sticky')));
        $allowedpositions = [
            CommerceStorefrontLayoutContract::HERO_INTEGRATED,
            CommerceStorefrontLayoutContract::SIDEBAR_STICKY,
            CommerceStorefrontLayoutContract::NONE,
        ];
        if (!in_array($position, $allowedpositions, true)) {
            $position = CommerceStorefrontLayoutContract::SIDEBAR_STICKY;
        }

        $shellmode = strtolower(trim((string)($submitted['shellmode'] ?? 'standard')));
        if (!in_array($shellmode, ['standard', 'fullwidth', 'landing', 'immersive'], true)) {
            $shellmode = 'standard';
        }

        $headermode = strtolower(trim((string)($submitted['headermode'] ?? 'automatic')));
        if (!in_array($headermode, ['automatic', 'builder', 'hidden'], true)) {
            $headermode = 'automatic';
        }

        $storefront['template'] = $template;
        $storefront['commerce_position'] =
            CommerceStorefrontLayoutContract::normalise_commerce_position(
                $position,
                $template === 'default' ? 'standard' : $template
            );
        $storefront['shell_mode'] = $shellmode;
        $storefront['product_header_mode'] = $headermode;
        $storefront['show_header'] = !empty($submitted['showheader']);
        $storefront['show_footer'] = !empty($submitted['showfooter']);

        // Advanced compatibility values remain supported without being part
        // of the main presentation workflow.
        if (array_key_exists('theme', $submitted)) {
            $theme = preg_replace(
                '/[^a-z0-9_-]/',
                '',
                strtolower(trim((string)$submitted['theme']))
            ) ?? '';
            $storefront['theme'] = $theme !== '' ? $theme : 'default';
        }
        if (array_key_exists('globalzones', $submitted)) {
            $storefront['global_zones'] =
                CommerceStorefrontLayoutContract::normalise_global_zones(
                    $submitted['globalzones']
                );
        }

        $metadata['storefront'] = $storefront;
        return $metadata;
    }
}
